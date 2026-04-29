<?php

use App\Data\Categories\UpdateCategoryData;
use App\Models\Category;
use App\Models\User;
use App\Services\Categories\CategoryUpdater;

it('updates only color when name is not provided', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create([
        'name' => 'Original',
        'slug' => 'original',
        'color' => '#000000',
    ]);

    app(CategoryUpdater::class)->update($category, new UpdateCategoryData(color: '#FFFFFF'));

    $category->refresh();
    expect($category->name)->toBe('Original')
        ->and($category->slug)->toBe('original')
        ->and($category->color)->toBe('#FFFFFF');
});

it('regenerates slug when name changes', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create([
        'name' => 'Original',
        'slug' => 'original',
    ]);

    app(CategoryUpdater::class)->update($category, new UpdateCategoryData(name: 'Renamed'));

    $category->refresh();
    expect($category->name)->toBe('Renamed')
        ->and($category->slug)->toBe('renamed');
});

it('disambiguates slug when target name conflicts with sibling', function () {
    $user = User::factory()->create();
    Category::factory()->for($user)->create(['name' => 'First', 'slug' => 'first']);
    $second = Category::factory()->for($user)->create(['name' => 'Second', 'slug' => 'second']);

    app(CategoryUpdater::class)->update($second, new UpdateCategoryData(name: 'First'));

    $second->refresh();
    expect($second->slug)->toBe('first-2');
});
