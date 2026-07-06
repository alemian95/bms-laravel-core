<?php

use App\Actions\Bookmarks\DeleteBookmark;
use App\Models\Bookmark;
use App\Models\User;

it('deletes the given bookmark', function () {
    $user = User::factory()->create();
    $bookmark = Bookmark::factory()->for($user)->create();

    app(DeleteBookmark::class)->handle($bookmark);

    $this->assertDatabaseMissing('bookmarks', ['id' => $bookmark->id]);
});
