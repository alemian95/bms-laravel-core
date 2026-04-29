<?php

use App\Models\Category;
use App\Models\User;
use App\Services\Categories\CategoryRemover;

it('deletes the given category', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create();

    app(CategoryRemover::class)->delete($category);

    $this->assertDatabaseMissing('categories', ['id' => $category->id]);
});
