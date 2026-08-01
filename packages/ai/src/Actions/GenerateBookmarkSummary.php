<?php

namespace BmsCore\Packages\Ai\Actions;

use App\Models\Bookmark;
use BmsCore\Packages\Ai\Models\AiSummary;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Genera il riassunto del contenuto di un bookmark e lo persiste.
 */
final class GenerateBookmarkSummary
{
    /**
     * @return AiSummary|null null se non c'è nulla da riassumere o se il plugin non è configurato
     */
    public function handle(Bookmark $input): ?AiSummary
    {
        $content = trim((string) $input->content_text);

        if ($content === '') {
            return null;
        }

        $model = (string) config('ai-summary.model');

        // Plugin attivo ma non configurato: non è un errore recuperabile con un
        // retry, e non deve far fallire la pipeline di parsing del core.
        if ($model === '') {
            Log::warning('Riassunto saltato: AI_SUMMARY_MODEL non configurato.', [
                'bookmark_id' => $input->id,
            ]);

            return null;
        }

        $summary = Str::of($content)->summarize(
            sentences: (int) config('ai-summary.sentences'),
            provider: config('ai-summary.provider'),
            model: $model,
            timeout: (int) config('ai-summary.timeout'),
        );

        return AiSummary::updateOrCreate(
            ['bookmark_id' => $input->id],
            ['summary' => $summary],
        );
    }
}
