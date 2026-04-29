<?php

use App\Models\Bookmark;
use App\Models\Category;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('returns the authenticated user bookmarks paginated', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    Bookmark::factory()->for($user)->count(3)->create();
    Bookmark::factory()->for($other)->count(5)->create();
    Sanctum::actingAs($user, ['bookmarks:read']);

    $response = $this->getJson('/api/v1/bookmarks');

    $response->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure([
            'data' => [['id', 'url', 'status']],
            'meta' => ['current_page', 'per_page', 'total'],
        ]);
});

it('filters by category_id', function () {
    $user = User::factory()->create();
    $tech = Category::factory()->for($user)->create();
    $news = Category::factory()->for($user)->create();
    Bookmark::factory()->for($user)->for($tech)->count(2)->create();
    Bookmark::factory()->for($user)->for($news)->count(4)->create();

    Sanctum::actingAs($user, ['bookmarks:read']);

    $response = $this->getJson('/api/v1/bookmarks?category_id='.$tech->id);

    $response->assertOk()->assertJsonCount(2, 'data');
});

it('respects per_page parameter', function () {
    $user = User::factory()->create();
    Bookmark::factory()->for($user)->count(7)->create();
    Sanctum::actingAs($user, ['bookmarks:read']);

    $response = $this->getJson('/api/v1/bookmarks?per_page=3');

    $response->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('meta.per_page', 3)
        ->assertJsonPath('meta.total', 7);
});

it('rejects per_page above the cap', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['bookmarks:read']);

    $this->getJson('/api/v1/bookmarks?per_page=500')
        ->assertStatus(422)
        ->assertJsonValidationErrors(['per_page']);
});

it('rejects unauthenticated requests', function () {
    $this->getJson('/api/v1/bookmarks')->assertUnauthorized();
});

it('rejects tokens without bookmarks:read ability', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['bookmarks:create']);

    $this->getJson('/api/v1/bookmarks')->assertForbidden();
});
