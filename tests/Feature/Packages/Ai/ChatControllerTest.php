<?php

use App\Models\Bookmark;
use App\Models\User;
use BmsCore\Packages\Ai\Agents\BookmarkChatAgent;
use BmsCore\Packages\Ai\Models\AiChatMessage;
use Illuminate\Testing\TestResponse;
use Laravel\Ai\Messages\MessageRole;

// Il provider openai-compatible esige un modello: in test ne basta uno finto,
// le chiamate non escono comunque grazie a BookmarkChatAgent::fake().
beforeEach(fn () => config(['ai-chat.model' => 'test-model']));

function parsedBookmarkFor(User $user): Bookmark
{
    return Bookmark::factory()->for($user)->create([
        'content_text' => 'Il testo integrale dell articolo.',
    ]);
}

/** Consuma la response SSE e ne restituisce il corpo completo. */
function streamedBody(TestResponse $response): string
{
    return $response->streamedContent();
}

it('returns the stored conversation of an owned bookmark', function () {
    $user = User::factory()->create();
    $bookmark = parsedBookmarkFor($user);

    AiChatMessage::create(['bookmark_id' => $bookmark->id, 'role' => MessageRole::User, 'content' => 'Di cosa parla?']);
    AiChatMessage::create(['bookmark_id' => $bookmark->id, 'role' => MessageRole::Assistant, 'content' => 'Di test.']);

    $this->actingAs($user)
        ->getJson(route('ai.chat.index', $bookmark))
        ->assertOk()
        ->assertJsonPath('messages.0.role', 'user')
        ->assertJsonPath('messages.0.content', 'Di cosa parla?')
        ->assertJsonPath('messages.1.role', 'assistant')
        ->assertJsonPath('messages.1.content', 'Di test.');
});

it('forbids reading the conversation of someone else bookmark', function () {
    $bookmark = parsedBookmarkFor(User::factory()->create());

    $this->actingAs(User::factory()->create())
        ->getJson(route('ai.chat.index', $bookmark))
        ->assertForbidden();
});

it('streams the answer and persists the turn', function () {
    BookmarkChatAgent::fake(['Parla di test automatici.']);

    $user = User::factory()->create();
    $bookmark = parsedBookmarkFor($user);

    $response = $this->actingAs($user)
        ->post(route('ai.chat.store', $bookmark), ['message' => 'Di cosa parla?']);

    $response->assertOk()->assertHeader('Content-Type', 'text/event-stream; charset=utf-8');

    expect(streamedBody($response))->toContain('text_delta')->toContain('[DONE]');

    $this->assertDatabaseHas('ai_chat_messages', [
        'bookmark_id' => $bookmark->id,
        'role' => 'user',
        'content' => 'Di cosa parla?',
    ]);

    $this->assertDatabaseHas('ai_chat_messages', [
        'bookmark_id' => $bookmark->id,
        'role' => 'assistant',
        'content' => 'Parla di test automatici.',
    ]);
});

it('feeds the article and the previous turns to the agent', function () {
    BookmarkChatAgent::fake(['Ecco.']);

    $user = User::factory()->create();
    $bookmark = parsedBookmarkFor($user);

    AiChatMessage::create(['bookmark_id' => $bookmark->id, 'role' => MessageRole::User, 'content' => 'Prima domanda.']);
    AiChatMessage::create(['bookmark_id' => $bookmark->id, 'role' => MessageRole::Assistant, 'content' => 'Prima risposta.']);

    $agent = new BookmarkChatAgent($bookmark);

    expect($agent->instructions())->toContain('Il testo integrale dell articolo.')
        ->and(collect($agent->messages())->pluck('content')->all())
        ->toBe(['Prima domanda.', 'Prima risposta.']);
});

it('rejects a question on a bookmark without parsed content', function () {
    BookmarkChatAgent::fake();

    $user = User::factory()->create();
    $bookmark = Bookmark::factory()->for($user)->create(['content_text' => null]);

    $this->actingAs($user)
        ->postJson(route('ai.chat.store', $bookmark), ['message' => 'Di cosa parla?'])
        ->assertStatus(422);

    BookmarkChatAgent::assertNeverPrompted();
});

it('reports a missing model instead of calling the provider', function () {
    BookmarkChatAgent::fake();
    config(['ai-chat.model' => null]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('ai.chat.store', parsedBookmarkFor($user)), ['message' => 'Di cosa parla?'])
        ->assertStatus(503);

    BookmarkChatAgent::assertNeverPrompted();
});

it('requires a non empty question', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('ai.chat.store', parsedBookmarkFor($user)), ['message' => ''])
        ->assertStatus(422)
        ->assertJsonValidationErrors('message');
});

it('forbids asking about someone else bookmark', function () {
    BookmarkChatAgent::fake();

    $bookmark = parsedBookmarkFor(User::factory()->create());

    $this->actingAs(User::factory()->create())
        ->postJson(route('ai.chat.store', $bookmark), ['message' => 'Di cosa parla?'])
        ->assertForbidden();

    BookmarkChatAgent::assertNeverPrompted();
});

it('clears the conversation of an owned bookmark', function () {
    $user = User::factory()->create();
    $bookmark = parsedBookmarkFor($user);

    AiChatMessage::create(['bookmark_id' => $bookmark->id, 'role' => MessageRole::User, 'content' => 'Di cosa parla?']);

    $this->actingAs($user)
        ->deleteJson(route('ai.chat.destroy', $bookmark))
        ->assertOk();

    $this->assertDatabaseCount('ai_chat_messages', 0);
});

it('forbids clearing the conversation of someone else bookmark', function () {
    $bookmark = parsedBookmarkFor(User::factory()->create());

    AiChatMessage::create(['bookmark_id' => $bookmark->id, 'role' => MessageRole::User, 'content' => 'Di cosa parla?']);

    $this->actingAs(User::factory()->create())
        ->deleteJson(route('ai.chat.destroy', $bookmark))
        ->assertForbidden();

    $this->assertDatabaseCount('ai_chat_messages', 1);
});
