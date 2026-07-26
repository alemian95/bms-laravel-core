<?php

namespace App\Actions\Bookmarks;

use App\Actions\Action;
use App\Data\Bookmarks\UpdateBookmarkProgressData;

/**
 * @implements Action<UpdateBookmarkProgressData, void>
 */
final class UpdateBookmarkProgress implements Action
{
    /**
     * @param  UpdateBookmarkProgressData  $input
     */
    public function handle(mixed $input): void
    {
        $input->bookmark->update([
            'scroll_position' => $input->progress,
            'reading_progress' => max($input->progress, $input->bookmark->reading_progress),
        ]);
    }
}
