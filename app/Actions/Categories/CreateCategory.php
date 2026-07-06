<?php

namespace App\Actions\Categories;

use App\Actions\Action;
use App\Data\Categories\CreateCategoryData;
use App\Models\Category;
use App\Services\Categories\CategorySlugGenerator;

/**
 * @implements Action<CreateCategoryData, Category>
 */
final class CreateCategory implements Action
{
    public function __construct(
        private CategorySlugGenerator $slugger,
    ) {}

    /**
     * @param  CreateCategoryData  $input
     */
    public function handle(mixed $input): Category
    {
        $user = $input->user;

        $attributes = [
            'user_id' => $user->id,
            'name' => $input->name,
            'slug' => $this->slugger->uniqueFor($user, $input->name),
        ];

        if ($input->color !== null) {
            $attributes['color'] = $input->color;
        }

        return Category::create($attributes);
    }
}
