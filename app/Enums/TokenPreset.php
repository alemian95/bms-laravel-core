<?php

namespace App\Enums;

enum TokenPreset: string
{
    case BrowserExtension = 'browser-extension';
    case MobileApp = 'mobile-app';
    case FullAccess = 'full-access';

    /**
     * @return array<int, string>
     */
    public function abilities(): array
    {
        return match ($this) {
            self::BrowserExtension => [
                TokenAbility::BookmarksCreate->value,
                TokenAbility::CategoriesRead->value,
            ],
            self::MobileApp => [
                TokenAbility::BookmarksRead->value,
                TokenAbility::BookmarksCreate->value,
                TokenAbility::CategoriesRead->value,
            ],
            self::FullAccess => ['*'],
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::BrowserExtension => 'Browser Extension',
            self::MobileApp => 'Mobile App',
            self::FullAccess => 'Full Access',
        };
    }
}
