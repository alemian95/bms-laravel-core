<?php

namespace App\Actions\Categories;

use App\Actions\Action;
use App\Data\Categories\UpdateCategoryData;
use App\Models\Category;
use App\Services\Categories\CategorySlugGenerator;

/**
 * @implements Action<UpdateCategoryData, Category>
 */
final class UpdateCategory implements Action
{
    public function __construct(
        private CategorySlugGenerator $slugger,
    ) {}

    /**
     * @param  UpdateCategoryData  $input
     */
    public function handle(mixed $input): Category
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
