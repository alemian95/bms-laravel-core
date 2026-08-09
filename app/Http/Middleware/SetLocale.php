<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Resolve the application locale from the user preference, falling back to
     * the browser's Accept-Language header and finally to the configured default.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var list<string> $supported */
        $supported = config('app.supported_locales');

        $preferred = $request->user()?->preferred_locale;

        App::setLocale(
            in_array($preferred, $supported, true)
                ? $preferred
                : $request->getPreferredLanguage($supported)
        );

        return $next($request);
    }
}
