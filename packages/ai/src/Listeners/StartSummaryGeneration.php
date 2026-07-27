<?php

namespace BmsCore\Packages\Ai\Listeners;

use App\Events\Bookmarks\ContentParsedEvent;
use BmsCore\Packages\Ai\Actions\GenerateBookmarkSummary;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Adapter fra l'evento del core e l'operazione di generazione.
 *
 * Va in coda per non condividere i retry di ParseArticleContentJob: una
 * chiamata AI fallita non deve far ri-scaricare e ri-parsare l'articolo.
 */
class StartSummaryGeneration implements ShouldQueue
{
    public function __construct(
        private GenerateBookmarkSummary $generateSummary,
    ) {}

    public function handle(ContentParsedEvent $event): void
    {
        $this->generateSummary->handle($event->bookmark);
    }

    public function tries(): int
    {
        return (int) config('ai-summary.tries');
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return (array) config('ai-summary.backoff');
    }
}
