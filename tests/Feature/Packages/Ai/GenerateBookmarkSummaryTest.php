<?php

use App\Events\Bookmarks\ContentParsedEvent;
use App\Models\Bookmark;
use BmsCore\Packages\Ai\Actions\GenerateBookmarkSummary;
use BmsCore\Packages\Ai\Listeners\StartSummaryGeneration;
use BmsCore\Packages\Ai\Models\AiSummary;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Queue;
use Laravel\Ai\Agents\SummarizeAgent;

// Il provider openai-compatible esige un modello: in test ne basta uno finto,
// le chiamate non escono comunque grazie a SummarizeAgent::fake().
beforeEach(fn () => config(['ai-summary.model' => 'test-model']));

it('generates and persists the summary of a parsed bookmark', function () {
    SummarizeAgent::fake(['Un riassunto generato dal modello.']);

    $bookmark = Bookmark::factory()->create(['content_text' => 'Il testo integrale dell articolo.']);

    $summary = app(GenerateBookmarkSummary::class)->handle($bookmark);

    expect($summary)->not->toBeNull()
        ->and($summary->summary)->toBe('Un riassunto generato dal modello.');

    $this->assertDatabaseHas('ai_summaries', [
        'bookmark_id' => $bookmark->id,
        'summary' => 'Un riassunto generato dal modello.',
    ]);

    SummarizeAgent::assertPrompted('Il testo integrale dell articolo.');
});

it('does nothing when the bookmark has no parsed content', function () {
    SummarizeAgent::fake();

    $bookmark = Bookmark::factory()->create(['content_text' => null]);

    expect(app(GenerateBookmarkSummary::class)->handle($bookmark))->toBeNull();

    $this->assertDatabaseCount('ai_summaries', 0);

    SummarizeAgent::assertNeverPrompted();
});

it('skips generation when no model is configured', function () {
    SummarizeAgent::fake();
    config(['ai-summary.model' => null]);

    $bookmark = Bookmark::factory()->create(['content_text' => 'Il testo integrale dell articolo.']);

    expect(app(GenerateBookmarkSummary::class)->handle($bookmark))->toBeNull();

    $this->assertDatabaseCount('ai_summaries', 0);

    SummarizeAgent::assertNeverPrompted();
});

it('replaces the existing summary instead of duplicating it', function () {
    SummarizeAgent::fake(['Il riassunto aggiornato.']);

    $bookmark = Bookmark::factory()->create(['content_text' => 'Il testo integrale dell articolo.']);
    AiSummary::create(['bookmark_id' => $bookmark->id, 'summary' => 'Il vecchio riassunto.']);

    app(GenerateBookmarkSummary::class)->handle($bookmark);

    expect(AiSummary::where('bookmark_id', $bookmark->id)->count())->toBe(1)
        ->and(AiSummary::where('bookmark_id', $bookmark->id)->value('summary'))->toBe('Il riassunto aggiornato.');
});

it('queues the generation when the core parses a bookmark', function () {
    Queue::fake();

    ContentParsedEvent::dispatch(Bookmark::factory()->create());

    Queue::assertPushed(
        CallQueuedListener::class,
        fn (CallQueuedListener $job) => $job->class === StartSummaryGeneration::class,
    );
});
