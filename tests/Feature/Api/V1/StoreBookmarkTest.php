<?php

use App\Jobs\ExtractBookmarkMetadataJob;
use App\Jobs\ParseArticleContentJob;
use App\Models\Bookmark;
use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Laravel\Sanctum\Sanctum;

it('stores a bookmark and dispatches the metadata + parse chain', function () {
    Bus::fake();
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['bookmarks:create']);

    $response = $this->postJson('/api/v1/bookmarks', [
        'url' => 'https://example.com/article',
    ]);

    $response->assertCreated()
        ->assertJsonStructure(['data' => ['id', 'url', 'status']])
        ->assertJsonPath('data.url', 'https://example.com/article')
        ->assertJsonPath('data.status', 'pending');

    Bus::assertChained([
        fn (ExtractBookmarkMetadataJob $job) => $job->bookmark->user_id === $user->id,
        fn (ParseArticleContentJob $job) => $job->bookmark->user_id === $user->id,
    ]);
});

it('stores a bookmark with a category id', function () {
    Bus::fake();
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create();
    Sanctum::actingAs($user, ['*']);

    $response = $this->postJson('/api/v1/bookmarks', [
        'url' => 'https://example.com/x',
        'category_id' => $category->id,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.category_id', $category->id);
});

it('returns 422 when url is missing', function () {
    Bus::fake();
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['bookmarks:create']);

    $response = $this->postJson('/api/v1/bookmarks', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['url']);
});

it('returns 422 when url is not a valid http(s) url', function () {
    Bus::fake();
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['bookmarks:create']);

    $response = $this->postJson('/api/v1/bookmarks', [
        'url' => 'ftp://example.com/file',
    ]);

    $response->assertStatus(422)->assertJsonValidationErrors(['url']);
});

it('returns 409 when the user already saved the same url', function () {
    Bus::fake();
    $user = User::factory()->create();
    Bookmark::factory()->for($user)->create(['url' => 'https://example.com/dup']);
    Sanctum::actingAs($user, ['bookmarks:create']);

    $response = $this->postJson('/api/v1/bookmarks', [
        'url' => 'https://example.com/dup',
    ]);

    $response->assertStatus(409)
        ->assertJson(['message' => 'Bookmark already exists.']);

    expect(Bookmark::where('user_id', $user->id)->count())->toBe(1);
});

it('returns 422 when category does not belong to user', function () {
    Bus::fake();
    $user = User::factory()->create();
    $other = User::factory()->create();
    $foreignCategory = Category::factory()->for($other)->create();
    Sanctum::actingAs($user, ['*']);

    $response = $this->postJson('/api/v1/bookmarks', [
        'url' => 'https://example.com/x',
        'category_id' => $foreignCategory->id,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['category_id']);

    expect(Bookmark::count())->toBe(0);
});

it('rejects unauthenticated requests', function () {
    $this->postJson('/api/v1/bookmarks', ['url' => 'https://example.com'])
        ->assertUnauthorized();
});

it('rejects tokens without bookmarks:create ability', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['bookmarks:read']);

    $this->postJson('/api/v1/bookmarks', ['url' => 'https://example.com'])
        ->assertForbidden();
});

it('normalizes the url before persisting', function () {
    Bus::fake();
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*']);

    $response = $this->postJson('/api/v1/bookmarks', [
        'url' => 'https://example.com/article#section',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.url', 'https://example.com/article');
});
