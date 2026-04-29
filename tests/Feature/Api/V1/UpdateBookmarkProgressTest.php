<?php

use App\Models\Bookmark;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('updates the reading progress for an owned bookmark', function () {
    $user = User::factory()->create();
    $bookmark = Bookmark::factory()->for($user)->create([
        'scroll_position' => 0,
        'reading_progress' => 10,
    ]);

    Sanctum::actingAs($user, ['bookmarks:update']);

    $response = $this->patchJson("/api/v1/bookmarks/{$bookmark->id}/progress", [
        'progress' => 55,
    ]);

    $response->assertNoContent();

    $bookmark->refresh();
    expect($bookmark->scroll_position)->toBe(55)
        ->and($bookmark->reading_progress)->toBe(55);
});

it('does not regress reading_progress when new value is lower', function () {
    $user = User::factory()->create();
    $bookmark = Bookmark::factory()->for($user)->create([
        'scroll_position' => 80,
        'reading_progress' => 80,
    ]);

    Sanctum::actingAs($user, ['bookmarks:update']);

    $this->patchJson("/api/v1/bookmarks/{$bookmark->id}/progress", ['progress' => 30])
        ->assertNoContent();

    $bookmark->refresh();
    expect($bookmark->scroll_position)->toBe(30)
        ->and($bookmark->reading_progress)->toBe(80);
});

it('forbids updating progress on a bookmark owned by another user', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $bookmark = Bookmark::factory()->for($other)->create();

    Sanctum::actingAs($user, ['bookmarks:update']);

    $this->patchJson("/api/v1/bookmarks/{$bookmark->id}/progress", ['progress' => 50])
        ->assertForbidden();
});

it('returns 422 when progress is missing or out of range', function () {
    $user = User::factory()->create();
    $bookmark = Bookmark::factory()->for($user)->create();

    Sanctum::actingAs($user, ['bookmarks:update']);

    $this->patchJson("/api/v1/bookmarks/{$bookmark->id}/progress", [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['progress']);

    $this->patchJson("/api/v1/bookmarks/{$bookmark->id}/progress", ['progress' => 150])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['progress']);
});

it('rejects unauthenticated requests', function () {
    $bookmark = Bookmark::factory()->create();

    $this->patchJson("/api/v1/bookmarks/{$bookmark->id}/progress", ['progress' => 10])
        ->assertUnauthorized();
});

it('rejects tokens without bookmarks:update ability', function () {
    $user = User::factory()->create();
    $bookmark = Bookmark::factory()->for($user)->create();

    Sanctum::actingAs($user, ['bookmarks:read']);

    $this->patchJson("/api/v1/bookmarks/{$bookmark->id}/progress", ['progress' => 10])
        ->assertForbidden();
});
