<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

it('shares the ai plugin flag so the frontend slot renders', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('bookmarks.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('plugins.ai', true));
});
