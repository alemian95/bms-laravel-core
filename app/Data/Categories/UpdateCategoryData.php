<?php

namespace App\Data\Categories;

final readonly class UpdateCategoryData
{
    public function __construct(
        public ?string $name = null,
        public ?string $color = null,
    ) {}

    public function hasName(): bool
    {
        return $this->name !== null;
    }
}
