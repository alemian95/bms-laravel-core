<?php

namespace BmsCore\Packages\Ai;

use App\Events\Bookmarks\ContentParsedEvent;
use BmsCore\Packages\Ai\Jobs\GenerateBookmarkSummaryJob;
use Illuminate\Support\Facades\Event;

class AiFeatureListenerRegistry
{
    public function registerListeners(): void
    {
        Event::listen(
            ContentParsedEvent::class,
            fn (ContentParsedEvent $event) => GenerateBookmarkSummaryJob::dispatch($event->bookmark),
        );
    }
}
