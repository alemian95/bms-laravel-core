<?php

namespace App\Data\Bookmarks;

final readonly class ListBookmarksFilters
{
    public function __construct(
        public ?string $query = null,
        public ?string $categorySlug = null,
        public int $page = 1,
        public int $perPage = 9,
    ) {}
}
