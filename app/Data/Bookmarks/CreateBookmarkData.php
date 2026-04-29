<?php

namespace App\Data\Bookmarks;

final readonly class CreateBookmarkData
{
    public function __construct(
        public string $url,
        public ?int $categoryId = null,
    ) {}
}
