<?php

use App\Enums\TokenAbility;
use App\Enums\TokenPreset;

it('browser extension preset has create+read abilities', function () {
    expect(TokenPreset::BrowserExtension->abilities())
        ->toBe([TokenAbility::BookmarksCreate->value, TokenAbility::CategoriesRead->value]);
});

it('mobile app preset has read+create+update+categories', function () {
    expect(TokenPreset::MobileApp->abilities())
        ->toBe([
            TokenAbility::BookmarksRead->value,
            TokenAbility::BookmarksCreate->value,
            TokenAbility::BookmarksUpdate->value,
            TokenAbility::CategoriesRead->value,
        ]);
});

it('full access preset has wildcard ability', function () {
    expect(TokenPreset::FullAccess->abilities())->toBe(['*']);
});
