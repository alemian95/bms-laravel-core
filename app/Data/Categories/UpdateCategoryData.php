<?php

namespace App\Data\Categories;

use App\Models\Category;

final readonly class UpdateCategoryData
{
    public function __construct(
        public Category $category,
        public ?string $name = null,
        public ?string $color = null,
    ) {}

    public function hasName(): bool
    {
        return $this->name !== null;
    }
}
