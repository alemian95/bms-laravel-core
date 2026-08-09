<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class LocaleController extends Controller
{
    /**
     * Persist the authenticated user's preferred UI locale.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', Rule::in(config('app.supported_locales'))],
        ]);

        $request->user()->update(['preferred_locale' => $validated['locale']]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Language updated.', locale: $validated['locale'])]);

        return back();
    }
}
