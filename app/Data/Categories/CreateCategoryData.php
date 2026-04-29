<?php

namespace App\Data\Categories;

final readonly class CreateCategoryData
{
    public function __construct(
        public string $name,
        public ?string $color = null,
    ) {}
}
