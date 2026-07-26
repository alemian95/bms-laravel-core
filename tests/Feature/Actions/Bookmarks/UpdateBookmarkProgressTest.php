<?php

use App\Actions\Bookmarks\UpdateBookmarkProgress;
use App\Data\Bookmarks\UpdateBookmarkProgressData;
use App\Models\Bookmark;
use App\Models\User;

it('updates scroll_position and bumps reading_progress to max seen', function () {
    $user = User::factory()->create();
    $bookmark = Bookmark::factory()->for($user)->create([
        'scroll_position' => 100,
        'reading_progress' => 30,
    ]);

    app(UpdateBookmarkProgress::class)->handle(new UpdateBookmarkProgressData($bookmark, 50));

    $bookmark->refresh();
    expect($bookmark->scroll_position)->toBe(50)
        ->and($bookmark->reading_progress)->toBe(50);
});

it('keeps reading_progress when new value is lower', function () {
    $user = User::factory()->create();
    $bookmark = Bookmark::factory()->for($user)->create([
        'scroll_position' => 100,
        'reading_progress' => 80,
    ]);

    app(UpdateBookmarkProgress::class)->handle(new UpdateBookmarkProgressData($bookmark, 40));

    $bookmark->refresh();
    expect($bookmark->scroll_position)->toBe(40)
        ->and($bookmark->reading_progress)->toBe(80);
});
