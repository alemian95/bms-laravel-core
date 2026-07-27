<?php

use App\Models\Bookmark;
use App\Models\User;
use BmsCore\Packages\Ai\Models\AiSummary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

it('renders the summary page for an owned bookmark', function () {
    $user = User::factory()->create();
    $bookmark = Bookmark::factory()->for($user)->create(['title' => 'A great read']);
    AiSummary::create(['bookmark_id' => $bookmark->id, 'summary' => 'Un riassunto generato.']);

    $this->actingAs($user)
        ->get(route('ai.summary', $bookmark))
        ->assertInertia(fn (AssertableInertia $page) => $page
            // false: Inertia looks for pages under resources/js only, this one lives in the package
            ->component('ai::summary', false)
            ->where('bookmark.title', 'A great read')
            ->where('summary', 'Un riassunto generato.')
        );
});

it('renders without a summary when none has been generated', function () {
    $user = User::factory()->create();
    $bookmark = Bookmark::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('ai.summary', $bookmark))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('summary', null));
});

it('forbids reading the summary of someone else bookmark', function () {
    $bookmark = Bookmark::factory()->for(User::factory())->create();

    $this->actingAs(User::factory()->create())
        ->get(route('ai.summary', $bookmark))
        ->assertForbidden();
});
