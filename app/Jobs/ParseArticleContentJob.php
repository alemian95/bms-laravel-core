<?php

namespace App\Jobs;

use App\Models\Bookmark;
use App\Services\ArticleContentParser;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ParseArticleContentJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [30, 120, 300];

    public function __construct(public Bookmark $bookmark) {}

    public function handle(ArticleContentParser $parser): void
    {
        $content = $parser->parse($this->bookmark->url);

        $this->bookmark->update($content);
    }
}
