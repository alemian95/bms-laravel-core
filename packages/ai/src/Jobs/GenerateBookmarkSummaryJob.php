<?php

namespace BmsCore\Packages\Ai\Jobs;

use App\Models\Bookmark;
use BmsCore\Packages\Ai\Actions\GenerateBookmarkSummary;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Unico punto d'ingresso in coda per la generazione: ci arrivano sia
 * l'evento del core sia l'avvio manuale dall'interfaccia.
 *
 * Sta in coda per non condividere i retry di ParseArticleContentJob: una
 * chiamata AI fallita non deve far ri-scaricare e ri-parsare l'articolo.
 */
class GenerateBookmarkSummaryJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public Bookmark $bookmark) {}

    public function handle(GenerateBookmarkSummary $generateSummary): void
    {
        $generateSummary->handle($this->bookmark);
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
