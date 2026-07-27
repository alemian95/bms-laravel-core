<?php

namespace BmsCore\Packages\Ai;

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
            ->discoversMigrations()
            ->runsMigrations();

        // register event listeners
        app(AiFeatureListenerRegistry::class)->registerListeners();
    }

    /**
     * Flags the package as available to the frontend plugin slots.
     */
    public function packageBooted(): void
    {
        Inertia::share('plugins.ai', true);
    }
}
