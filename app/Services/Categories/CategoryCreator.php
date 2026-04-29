<?php

namespace App\Services\Categories;

use App\Data\Categories\CreateCategoryData;
use App\Models\Category;
use App\Models\User;

class CategoryCreator
{
    public function __construct(
        private CategorySlugGenerator $slugger,
    ) {}

    public function create(User $user, CreateCategoryData $data): Category
    {
        $attributes = [
            'user_id' => $user->id,
            'name' => $data->name,
            'slug' => $this->slugger->uniqueFor($user, $data->name),
        ];

        if ($data->color !== null) {
            $attributes['color'] = $data->color;
        }

        return Category::create($attributes);
    }
}
