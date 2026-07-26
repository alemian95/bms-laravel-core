<?php

namespace App\Actions\Categories;

use App\Actions\Action;
use App\Models\Category;

/**
 * @implements Action<Category, void>
 */
final class DeleteCategory implements Action
{
    /**
     * @param  Category  $input
     */
    public function handle(mixed $input): void
    {
        $input->delete();
    }
}
