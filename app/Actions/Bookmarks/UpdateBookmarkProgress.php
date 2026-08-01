<?php

namespace App\Actions\Bookmarks;

use App\Data\Bookmarks\UpdateBookmarkProgressData;

final class UpdateBookmarkProgress
{
    public function handle(UpdateBookmarkProgressData $input): void
    {
        $input->bookmark->update([
            'scroll_position' => $input->progress,
            'reading_progress' => max($input->progress, $input->bookmark->reading_progress),
        ]);
    }
}
