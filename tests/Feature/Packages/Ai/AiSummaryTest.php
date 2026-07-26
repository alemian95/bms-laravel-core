<?php

use App\Models\Bookmark;
use BmsCore\Packages\Ai\Models\AiSummary;

it('persists a summary attached to a bookmark', function () {
    $bookmark = Bookmark::factory()->create();

    $summary = AiSummary::create([
        'bookmark_id' => $bookmark->id,
        'summary' => 'Un riassunto generato.',
    ]);

    expect($summary->refresh()->summary)->toBe('Un riassunto generato.')
        ->and($summary->bookmark->is($bookmark))->toBeTrue();
});
