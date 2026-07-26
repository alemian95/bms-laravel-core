<?php

use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;
use BmsCore\Packages\Ai\AiFeatureServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    AiFeatureServiceProvider::class,
];
