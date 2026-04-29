<?php

namespace App\Enums;

enum TokenAbility: string
{
    case BookmarksRead = 'bookmarks:read';
    case BookmarksCreate = 'bookmarks:create';
    case BookmarksUpdate = 'bookmarks:update';
    case BookmarksDelete = 'bookmarks:delete';
    case CategoriesRead = 'categories:read';
    case CategoriesWrite = 'categories:write';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
