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

// Il nome dello slot è l'unico contratto fra la pagina del core e il plugin:
// se il core lo rinomina, il pannello di chat sparisce senza nessun test rosso.
it('registers the chat panel on a slot the reader page actually renders', function () {
    $slots = file_get_contents(base_path('packages/ai/resources/js/slots.tsx'));
    $readerPage = file_get_contents(base_path('resources/js/pages/bookmarks/read.tsx'));

    expect($slots)->toContain("'bookmark-read-aside'")
        ->and($readerPage)->toContain('bookmark-read-aside');
});

it('shares a null summary on pages without a bookmark', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('bookmarks.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('aiSummary', null));
});
