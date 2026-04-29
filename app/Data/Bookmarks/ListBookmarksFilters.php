<?php

namespace App\Data\Bookmarks;

final readonly class ListBookmarksFilters
{
    /**
     * @param  array<string, mixed>  $queryParams
     */
    public function __construct(
        public ?string $query = null,
        public ?string $categorySlug = null,
        public int $page = 1,
        public int $perPage = 9,
        public string $path = '',
        public array $queryParams = [],
    ) {}
}
