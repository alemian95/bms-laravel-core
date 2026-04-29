<?php

use App\Models\Bookmark;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('deletes an owned bookmark', function () {
    $user = User::factory()->create();
    $bookmark = Bookmark::factory()->for($user)->create();

    Sanctum::actingAs($user, ['bookmarks:delete']);

    $response = $this->deleteJson("/api/v1/bookmarks/{$bookmark->id}");

    $response->assertNoContent();
    $this->assertDatabaseMissing('bookmarks', ['id' => $bookmark->id]);
});

it('forbids deleting a bookmark owned by another user', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $bookmark = Bookmark::factory()->for($other)->create();

    Sanctum::actingAs($user, ['bookmarks:delete']);

    $this->deleteJson("/api/v1/bookmarks/{$bookmark->id}")->assertForbidden();
    $this->assertDatabaseHas('bookmarks', ['id' => $bookmark->id]);
});

it('returns 404 when bookmark does not exist', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['bookmarks:delete']);

    $this->deleteJson('/api/v1/bookmarks/999999')->assertNotFound();
});

it('rejects unauthenticated requests', function () {
    $bookmark = Bookmark::factory()->create();

    $this->deleteJson("/api/v1/bookmarks/{$bookmark->id}")->assertUnauthorized();
});

it('rejects tokens without bookmarks:delete ability', function () {
    $user = User::factory()->create();
    $bookmark = Bookmark::factory()->for($user)->create();

    Sanctum::actingAs($user, ['bookmarks:read']);

    $this->deleteJson("/api/v1/bookmarks/{$bookmark->id}")->assertForbidden();
});
