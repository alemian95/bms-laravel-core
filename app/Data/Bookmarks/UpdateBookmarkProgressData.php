<?php

namespace App\Data\Bookmarks;

use App\Models\Bookmark;

final readonly class UpdateBookmarkProgressData
{
    public function __construct(
        public Bookmark $bookmark,
        public int $progress,
    ) {}
}
