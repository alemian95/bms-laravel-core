<?php

namespace App\Services\Bookmarks;

use App\Models\Bookmark;

class BookmarkRemover
{
    public function delete(Bookmark $bookmark): void
    {
        $bookmark->delete();
    }
}
