<?php

namespace App\Jobs;

use App\Models\Bookmark;
use App\Services\BookmarkMetadataExtractor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ExtractBookmarkMetadataJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [30, 120, 300];

    public function __construct(public Bookmark $bookmark) {}

    public function handle(BookmarkMetadataExtractor $extractor): void
    {
        $metadata = $extractor->extract($this->bookmark->url);

        $this->bookmark->update([
            ...$metadata,
            'status' => 'parsed',
        ]);
    }

    public function failed(Throwable $e): void
    {
        $this->bookmark->update(['status' => 'failed']);
    }
}
