<?php

namespace App\Data\Bookmarks;

use App\Models\User;

final readonly class CreateBookmarkData
{
    public function __construct(
        public User $user,
        public string $url,
        public ?int $categoryId = null,
    ) {}
}
