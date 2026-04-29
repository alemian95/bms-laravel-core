<?php

namespace App\Services\Categories;

use App\Data\Categories\UpdateCategoryData;
use App\Models\Category;

class CategoryUpdater
{
    public function __construct(
        private CategorySlugGenerator $slugger,
    ) {}

    public function update(Category $category, UpdateCategoryData $data): Category
    {
        $attributes = [];

        if ($data->hasName()) {
            $attributes['name'] = $data->name;
            $attributes['slug'] = $this->slugger->uniqueFor(
                $category->user,
                $data->name,
                exceptId: $category->id,
            );
        }

        if ($data->color !== null) {
            $attributes['color'] = $data->color;
        }

        if ($attributes !== []) {
            $category->update($attributes);
        }

        return $category;
    }
}
