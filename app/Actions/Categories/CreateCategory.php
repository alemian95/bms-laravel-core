<?php

namespace App\Actions\Categories;

use App\Data\Categories\CreateCategoryData;
use App\Models\Category;
use App\Services\Categories\CategorySlugGenerator;

final class CreateCategory
{
    public function __construct(
        private CategorySlugGenerator $slugger,
    ) {}

    public function handle(CreateCategoryData $input): Category
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
