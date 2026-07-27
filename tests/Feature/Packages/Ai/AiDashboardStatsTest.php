<?php

use App\Models\Bookmark;
use App\Models\User;
use BmsCore\Packages\Ai\Models\AiSummary;
use Inertia\Testing\AssertableInertia;

it('reports summary coverage scoped to the authenticated user', function () {
    $user = User::factory()->create();
    $stranger = User::factory()->create();

    $bookmarks = Bookmark::factory()->count(3)->for($user)->create();
    AiSummary::create(['bookmark_id' => $bookmarks->first()->id, 'summary' => 'A summary']);

    $strangerBookmark = Bookmark::factory()->for($stranger)->create();
    AiSummary::create(['bookmark_id' => $strangerBookmark->id, 'summary' => 'Not mine']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('aiStats.summarized', 1)
            ->where('aiStats.bookmarks', 3)
            ->has('aiStats.recent', 1)
            ->has('aiStats.weekly', 12)
        );
});

it('does not share ai stats with guests', function () {
    $this->get(route('home'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('aiStats', null));
});
