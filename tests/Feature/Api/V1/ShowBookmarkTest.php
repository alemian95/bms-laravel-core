<?php

use App\Models\Bookmark;
use App\Models\Category;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('returns full payload for an owned bookmark with loaded category', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['name' => 'Tech']);
    $bookmark = Bookmark::factory()->for($user)->for($category)->create([
        'title' => 'Sample article',
        'content_html' => '<p>Body</p>',
        'status' => 'parsed',
    ]);

    Sanctum::actingAs($user, ['bookmarks:read']);

    $response = $this->getJson("/api/v1/bookmarks/{$bookmark->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $bookmark->id)
        ->assertJsonPath('data.title', 'Sample article')
        ->assertJsonPath('data.category.id', $category->id)
        ->assertJsonPath('data.category.name', 'Tech');
});

it('forbids access to a bookmark owned by another user', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $bookmark = Bookmark::factory()->for($other)->create();

    Sanctum::actingAs($user, ['bookmarks:read']);

    $this->getJson("/api/v1/bookmarks/{$bookmark->id}")->assertForbidden();
});

it('returns 404 when bookmark does not exist', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['bookmarks:read']);

    $this->getJson('/api/v1/bookmarks/999999')->assertNotFound();
});

it('rejects unauthenticated requests', function () {
    $bookmark = Bookmark::factory()->create();

    $this->getJson("/api/v1/bookmarks/{$bookmark->id}")->assertUnauthorized();
});

it('rejects tokens without bookmarks:read ability', function () {
    $user = User::factory()->create();
    $bookmark = Bookmark::factory()->for($user)->create();
    Sanctum::actingAs($user, ['bookmarks:create']);

    $this->getJson("/api/v1/bookmarks/{$bookmark->id}")->assertForbidden();
});
