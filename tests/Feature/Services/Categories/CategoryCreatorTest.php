<?php

use App\Data\Categories\CreateCategoryData;
use App\Models\Category;
use App\Models\User;
use App\Services\Categories\CategoryCreator;

it('creates a category with auto-generated slug for the user', function () {
    $user = User::factory()->create();

    $category = app(CategoryCreator::class)->create(
        $user,
        new CreateCategoryData(name: 'My Reads', color: '#FF0000'),
    );

    expect($category)->toBeInstanceOf(Category::class)
        ->and($category->user_id)->toBe($user->id)
        ->and($category->name)->toBe('My Reads')
        ->and($category->slug)->toBe('my-reads')
        ->and($category->color)->toBe('#FF0000');
});

it('disambiguates slug on conflict for the same user', function () {
    $user = User::factory()->create();
    Category::factory()->for($user)->create(['slug' => 'tech']);

    $category = app(CategoryCreator::class)->create(
        $user,
        new CreateCategoryData(name: 'Tech'),
    );

    expect($category->slug)->toBe('tech-2');
});
