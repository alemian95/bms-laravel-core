<?php

namespace BmsCore\Packages\Ai;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class AiFeatureServiceProvider extends PackageServiceProvider
{
    // https://github.com/spatie/package-skeleton-laravel/blob/main/src/SkeletonServiceProvider.php
    public function configurePackage(Package $package): void
    {
        $package->name('ai')
            ->discoversMigrations()
            ->runsMigrations();
    }
}
