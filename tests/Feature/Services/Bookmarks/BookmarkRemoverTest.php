<?php

use App\Models\Bookmark;
use App\Models\User;
use App\Services\Bookmarks\BookmarkRemover;

it('deletes the given bookmark', function () {
    $user = User::factory()->create();
    $bookmark = Bookmark::factory()->for($user)->create();

    app(BookmarkRemover::class)->delete($bookmark);

    $this->assertDatabaseMissing('bookmarks', ['id' => $bookmark->id]);
});
