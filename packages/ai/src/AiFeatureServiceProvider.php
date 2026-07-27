<?php

namespace BmsCore\Packages\Ai;

use BmsCore\Packages\Ai\Services\AiDashboardStats;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class AiFeatureServiceProvider extends PackageServiceProvider
{
    // https://github.com/spatie/package-skeleton-laravel/blob/main/src/SkeletonServiceProvider.php
    public function configurePackage(Package $package): void
    {
        // register package
        $package->name('ai')
            ->hasConfigFile('ai-summary')
            ->discoversMigrations()
            ->runsMigrations()
            ->hasRoute('web');

        // register event listeners
        app(AiFeatureListenerRegistry::class)->registerListeners();
    }

    /**
     * Flags the package as available to the frontend plugin slots and feeds
     * the data its `dashboard-widgets` slot renders.
     *
     * ponytail: the closure runs on every Inertia response, not just the
     * dashboard, for three cheap aggregates. Cache it if it ever shows up in a
     * profile — scoping it to a core route name would couple the package to it.
     */
    public function packageBooted(): void
    {
        Inertia::share('plugins.ai', true);

        Inertia::share('aiStats', fn () => ($user = Auth::user())
            ? app(AiDashboardStats::class)->for($user)
            : null);
    }
}
