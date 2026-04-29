<?php

namespace App\Services\Categories;

use App\Models\Category;

class CategoryRemover
{
    public function delete(Category $category): void
    {
        $category->delete();
    }
}
