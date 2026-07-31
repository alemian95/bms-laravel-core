<?php

use App\Models\Bookmark;
use App\Models\User;
use BmsCore\Packages\Ai\Models\AiSummary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

it('shares the ai plugin flag so the frontend slot renders', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('bookmarks.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('plugins.ai', true));
});

it('shares the summary of the bookmark being read', function () {
    $user = User::factory()->create();
    $bookmark = Bookmark::factory()->for($user)->create();

    AiSummary::create([
        'bookmark_id' => $bookmark->id,
        'summary' => 'Il riassunto TLDR.',
    ]);

    $this->actingAs($user)
        ->get(route('bookmarks.read', $bookmark))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('aiSummary', 'Il riassunto TLDR.'));
});

it('shares a null summary on pages without a bookmark', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('bookmarks.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('aiSummary', null));
});
