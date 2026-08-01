<?php

namespace App\Actions\Categories;

use App\Data\Categories\UpdateCategoryData;
use App\Models\Category;
use App\Services\Categories\CategorySlugGenerator;

final class UpdateCategory
{
    public function __construct(
        private CategorySlugGenerator $slugger,
    ) {}

    public function handle(UpdateCategoryData $input): Category
    {
        $category = $input->category;
        $attributes = [];

        if ($input->hasName()) {
            $attributes['name'] = $input->name;
            $attributes['slug'] = $this->slugger->uniqueFor(
                $category->user,
                $input->name,
                exceptId: $category->id,
            );
        }

        if ($input->color !== null) {
            $attributes['color'] = $input->color;
        }

        if ($attributes !== []) {
            $category->update($attributes);
        }

        return $category;
    }
}
