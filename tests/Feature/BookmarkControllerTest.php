<?php

use App\Jobs\ExtractBookmarkMetadataJob;
use App\Jobs\ParseArticleContentJob;
use App\Models\Bookmark;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('stores a bookmark and chains metadata then parse content jobs', function () {
    Bus::fake();
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('bookmarks.store'), [
        'url' => 'https://example.com/article',
    ]);

    $response->assertRedirect(route('bookmarks.index'));

    $bookmark = Bookmark::where('user_id', $user->id)->firstOrFail();
    expect($bookmark->status)->toBe('pending')
        ->and($bookmark->url)->toBe('https://example.com/article');

    Bus::assertChained([
        fn (ExtractBookmarkMetadataJob $job) => $job->bookmark->is($bookmark),
        fn (ParseArticleContentJob $job) => $job->bookmark->is($bookmark),
    ]);
});

test('read renders reader page for owned bookmark', function () {
    $user = User::factory()->create();
    $bookmark = Bookmark::factory()->for($user)->create([
        'title' => 'A great read',
        'content_html' => '<p>Body</p>',
    ]);

    $response = $this->actingAs($user)->get(route('bookmarks.read', $bookmark));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('bookmarks/read')
        ->where('bookmark.id', $bookmark->id)
        ->where('bookmark.content_html', '<p>Body</p>')
    );
});

test('read forbids access to another user bookmark', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $bookmark = Bookmark::factory()->for($other)->create();

    $response = $this->actingAs($user)->get(route('bookmarks.read', $bookmark));

    $response->assertForbidden();
});

test('stores a bookmark linked to a category', function () {
    Queue::fake();
    $user = User::factory()->create();
    $category = Category::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)->post(route('bookmarks.store'), [
        'url' => 'https://example.com/with-category',
        'category_id' => $category->id,
    ]);

    $this->assertDatabaseHas('bookmarks', [
        'user_id' => $user->id,
        'url' => 'https://example.com/with-category',
        'category_id' => $category->id,
        'status' => 'pending',
    ]);
});

test('rejects a category owned by a different user', function () {
    Queue::fake();
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $category = Category::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($user)->post(route('bookmarks.store'), [
        'url' => 'https://example.com/foreign-cat',
        'category_id' => $category->id,
    ]);

    $this->assertDatabaseMissing('bookmarks', [
        'url' => 'https://example.com/foreign-cat',
    ]);
});

test('validates url format', function () {
    Queue::fake();
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('bookmarks.store'), [
        'url' => 'not-a-url',
    ]);

    $response->assertSessionHasErrors('url');
    Queue::assertNothingPushed();
});

test('normalizes url by stripping fragment', function () {
    Queue::fake();
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('bookmarks.store'), [
        'url' => 'https://example.com/article#heading',
    ]);

    $this->assertDatabaseHas('bookmarks', [
        'user_id' => $user->id,
        'url' => 'https://example.com/article',
    ]);
});

test('rejects duplicate urls for the same user', function () {
    Queue::fake();
    $user = User::factory()->create();
    Bookmark::factory()->for($user)->create(['url' => 'https://example.com/dup']);

    $response = $this->actingAs($user)->post(route('bookmarks.store'), [
        'url' => 'https://example.com/dup',
    ]);

    $response->assertSessionHasErrors('url');
    expect(Bookmark::where('user_id', $user->id)->count())->toBe(1);
    Queue::assertNothingPushed();
});

test('allows same url for different users', function () {
    Queue::fake();
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    Bookmark::factory()->for($userA)->create(['url' => 'https://example.com/shared']);

    $this->actingAs($userB)->post(route('bookmarks.store'), [
        'url' => 'https://example.com/shared',
    ]);

    expect(Bookmark::where('url', 'https://example.com/shared')->count())->toBe(2);
});

test('index returns only current user bookmarks', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    Bookmark::factory()->for($user)->count(2)->create();
    Bookmark::factory()->for($other)->count(3)->create();

    $response = $this->actingAs($user)->get(route('bookmarks.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('bookmarks/index')
        ->has('bookmarks.data', 2)
    );
});

test('index filters bookmarks by category slug', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create(['user_id' => $user->id, 'slug' => 'tech']);
    $otherCategory = Category::factory()->create(['user_id' => $user->id, 'slug' => 'news']);

    Bookmark::factory()->for($user)->for($category)->count(2)->create();
    Bookmark::factory()->for($user)->for($otherCategory)->count(3)->create();
    Bookmark::factory()->for($user)->create(['category_id' => null]);

    $response = $this->actingAs($user)->get(route('bookmarks.index', ['category' => 'tech']));

    $response->assertInertia(fn ($page) => $page
        ->where('activeCategory', 'tech')
        ->has('bookmarks.data', 2)
    );
});

test('user can delete own bookmark', function () {
    $user = User::factory()->create();
    $bookmark = Bookmark::factory()->for($user)->create();

    $response = $this->actingAs($user)->delete(route('bookmarks.destroy', $bookmark));

    $response->assertRedirect(route('bookmarks.index'));
    $this->assertDatabaseMissing('bookmarks', ['id' => $bookmark->id]);
});

test('user cannot delete another user bookmark', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $bookmark = Bookmark::factory()->for($other)->create();

    $response = $this->actingAs($user)->delete(route('bookmarks.destroy', $bookmark));

    $response->assertForbidden();
    $this->assertDatabaseHas('bookmarks', ['id' => $bookmark->id]);
});

test('guests cannot access bookmark routes', function () {
    $this->get(route('bookmarks.index'))->assertRedirect();
    $this->post(route('bookmarks.store'), ['url' => 'https://example.com'])->assertRedirect();
});
