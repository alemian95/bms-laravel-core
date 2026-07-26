<?php

use App\Actions\Categories\DeleteCategory;
use App\Models\Category;
use App\Models\User;

it('deletes the given category', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create();

    app(DeleteCategory::class)->handle($category);

    $this->assertDatabaseMissing('categories', ['id' => $category->id]);
});
