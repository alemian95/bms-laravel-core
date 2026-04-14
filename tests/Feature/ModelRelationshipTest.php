<?php

use App\Models\Bookmark;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a user can have many categories', function () {
    $user = User::factory()->create();
    Category::factory(3)->create(['user_id' => $user->id]);

    expect($user->categories)->toHaveCount(3)
        ->and($user->categories->first())->toBeInstanceOf(Category::class);
});

test('a user can have many bookmarks', function () {
    $user = User::factory()->create();
    Bookmark::factory(3)->create(['user_id' => $user->id]);

    expect($user->bookmarks)->toHaveCount(3)
        ->and($user->bookmarks->first())->toBeInstanceOf(Bookmark::class);
});

test('a category belongs to a user', function () {
    $category = Category::factory()->create();

    expect($category->user)->toBeInstanceOf(User::class);
});

test('a category can have many bookmarks', function () {
    $category = Category::factory()->create();
    Bookmark::factory(3)->create([
        'user_id' => $category->user_id,
        'category_id' => $category->id,
    ]);

    expect($category->bookmarks)->toHaveCount(3)
        ->and($category->bookmarks->first())->toBeInstanceOf(Bookmark::class);
});

test('a bookmark belongs to a user', function () {
    $bookmark = Bookmark::factory()->create();

    expect($bookmark->user)->toBeInstanceOf(User::class);
});

test('a bookmark belongs to a category', function () {
    $category = Category::factory()->create();
    $bookmark = Bookmark::factory()->create([
        'user_id' => $category->user_id,
        'category_id' => $category->id,
    ]);

    expect($bookmark->category)->toBeInstanceOf(Category::class)
        ->and($bookmark->category->id)->toBe($category->id);
});

test('a bookmark can have no category', function () {
    $bookmark = Bookmark::factory()->create(['category_id' => null]);

    expect($bookmark->category)->toBeNull();
});
