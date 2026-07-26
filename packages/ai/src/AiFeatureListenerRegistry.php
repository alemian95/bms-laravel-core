<?php

namespace BmsCore\Packages\Ai;

use App\Events\Bookmarks\ContentParsedEvent;
use BmsCore\Packages\Ai\Listeners\StartSummaryGeneration;
use Illuminate\Support\Facades\Event;

class AiFeatureListenerRegistry
{
    public function registerListeners(): void
    {
        Event::listen(
            ContentParsedEvent::class,
            StartSummaryGeneration::class
        );
    }
}
