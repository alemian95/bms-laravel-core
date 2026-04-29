<?php

namespace App\Services\Bookmarks;

use App\Models\Bookmark;

class BookmarkProgressUpdater
{
    public function update(Bookmark $bookmark, int $progress): void
    {
        $bookmark->update([
            'scroll_position' => $progress,
            'reading_progress' => max($progress, $bookmark->reading_progress),
        ]);
    }
}
