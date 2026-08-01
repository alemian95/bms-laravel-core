<?php

namespace App\Actions\Bookmarks;

use App\Models\Bookmark;

final class DeleteBookmark
{
    public function handle(Bookmark $input): void
    {
        $input->delete();
    }
}
