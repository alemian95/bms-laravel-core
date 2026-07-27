<?php

use App\Models\Bookmark;
use App\Models\User;
use BmsCore\Packages\Ai\Jobs\GenerateBookmarkSummaryJob;
use BmsCore\Packages\Ai\Models\AiSummary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
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

it('queues the generation when started manually', function () {
    Queue::fake();

    $user = User::factory()->create();
    $bookmark = Bookmark::factory()->for($user)->create(['content_text' => 'Il testo integrale.']);

    $this->actingAs($user)
        ->from(route('ai.summary', $bookmark))
        ->post(route('ai.generate-summary', $bookmark))
        ->assertRedirect(route('ai.summary', $bookmark));

    Queue::assertPushed(
        GenerateBookmarkSummaryJob::class,
        fn (GenerateBookmarkSummaryJob $job) => $job->bookmark->is($bookmark),
    );
});

it('does not queue anything when there is no parsed content', function () {
    Queue::fake();

    $user = User::factory()->create();
    $bookmark = Bookmark::factory()->for($user)->create(['content_text' => null]);

    $this->actingAs($user)
        ->from(route('ai.summary', $bookmark))
        ->post(route('ai.generate-summary', $bookmark))
        ->assertRedirect(route('ai.summary', $bookmark));

    Queue::assertNothingPushed();
});

it('forbids starting the generation on someone else bookmark', function () {
    Queue::fake();

    $bookmark = Bookmark::factory()->for(User::factory())->create(['content_text' => 'Il testo integrale.']);

    $this->actingAs(User::factory()->create())
        ->post(route('ai.generate-summary', $bookmark))
        ->assertForbidden();

    Queue::assertNothingPushed();
});
