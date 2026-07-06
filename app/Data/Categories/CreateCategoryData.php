<?php

namespace App\Data\Categories;

use App\Models\User;

final readonly class CreateCategoryData
{
    public function __construct(
        public User $user,
        public string $name,
        public ?string $color = null,
    ) {}
}
