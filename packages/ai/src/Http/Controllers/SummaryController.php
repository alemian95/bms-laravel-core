<?php

namespace BmsCore\Packages\Ai\Http\Controllers;

use App\Models\Bookmark;
use BmsCore\Packages\Ai\Models\AiSummary;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SummaryController
{
    public function __invoke(Bookmark $bookmark): Response
    {
        Gate::authorize('view', $bookmark);

        return Inertia::render('ai::summary', [
            'bookmark' => $bookmark->only(['id', 'title', 'url', 'domain']),
            'summary' => AiSummary::where('bookmark_id', $bookmark->id)->value('summary'),
        ]);
    }
}
