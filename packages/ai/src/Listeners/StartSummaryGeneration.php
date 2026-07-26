<?php

namespace BmsCore\Packages\Ai\Listeners;

use App\Events\Bookmarks\ContentParsedEvent;
use Illuminate\Support\Facades\Log;

class StartSummaryGeneration
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {}

    /**
     * Handle the event.
     */
    public function handle(ContentParsedEvent $event): void
    {
        Log::info('Start summary generation for bookmark: ' . $event->bookmark->id);
    }
}
