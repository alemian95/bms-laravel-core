<?php

namespace App\Actions\Bookmarks;

use App\Actions\Action;
use App\Models\Bookmark;

/**
 * @implements Action<Bookmark, void>
 */
final class DeleteBookmark implements Action
{
    /**
     * @param  Bookmark  $input
     */
    public function handle(mixed $input): void
    {
        $input->delete();
    }
}
