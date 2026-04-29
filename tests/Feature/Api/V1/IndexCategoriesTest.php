<?php

use App\Models\Category;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('returns the authenticated user categories ordered by name', function () {
    $user = User::factory()->create();
    Category::factory()->for($user)->create(['name' => 'Zebra']);
    Category::factory()->for($user)->create(['name' => 'Alpha']);
    Category::factory()->for(User::factory())->create(['name' => 'Foreign']);

    Sanctum::actingAs($user, ['categories:read']);

    $response = $this->getJson('/api/v1/categories');

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.name', 'Alpha')
        ->assertJsonPath('data.1.name', 'Zebra');
});

it('rejects unauthenticated requests', function () {
    $this->getJson('/api/v1/categories')->assertUnauthorized();
});

it('rejects tokens without categories:read ability', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['bookmarks:create']);

    $this->getJson('/api/v1/categories')->assertForbidden();
});

it('accepts wildcard ability', function () {
    $user = User::factory()->create();
    Category::factory()->for($user)->create();
    Sanctum::actingAs($user, ['*']);

    $this->getJson('/api/v1/categories')->assertOk();
});
