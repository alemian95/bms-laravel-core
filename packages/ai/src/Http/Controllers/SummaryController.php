<?php

namespace BmsCore\Packages\Ai\Http\Controllers;

use App\Models\Bookmark;
use BmsCore\Packages\Ai\Jobs\GenerateBookmarkSummaryJob;
use BmsCore\Packages\Ai\Models\AiSummary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SummaryController
{
    public function show(Bookmark $bookmark): Response
    {
        Gate::authorize('view', $bookmark);

        return Inertia::render('ai::summary', [
            'bookmark' => $bookmark->only(['id', 'title', 'url', 'domain']),
            'summary' => AiSummary::where('bookmark_id', $bookmark->id)->value('summary'),
        ]);
    }

    /**
     * Avvio manuale della generazione: stessa coda dell'evento del core.
     *
     * ponytail: nessun lock, due click ravvicinati accodano due job che
     * scrivono lo stesso record via updateOrCreate. Se il costo delle chiamate
     * doppie pesa, un lock atomico su bookmark_id è la via.
     */
    public function store(Bookmark $bookmark): RedirectResponse
    {
        Gate::authorize('update', $bookmark);

        if (trim((string) $bookmark->content_text) === '') {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'No parsed content to summarize yet.']);

            return back();
        }

        GenerateBookmarkSummaryJob::dispatch($bookmark);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Summary generation started...']);

        return back();
    }
}
