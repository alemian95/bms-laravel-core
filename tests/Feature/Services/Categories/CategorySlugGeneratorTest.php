<?php

use App\Models\Category;
use App\Models\User;
use App\Services\Categories\CategorySlugGenerator;

it('generates a slug from name when no conflicts exist', function () {
    $user = User::factory()->create();

    $slug = app(CategorySlugGenerator::class)->uniqueFor($user, 'My Cool Category');

    expect($slug)->toBe('my-cool-category');
});

it('appends an incrementing suffix on conflict', function () {
    $user = User::factory()->create();
    Category::factory()->for($user)->create(['slug' => 'tech']);

    $slug = app(CategorySlugGenerator::class)->uniqueFor($user, 'Tech');

    expect($slug)->toBe('tech-2');
});

it('skips taken suffixes', function () {
    $user = User::factory()->create();
    Category::factory()->for($user)->create(['slug' => 'tech']);
    Category::factory()->for($user)->create(['slug' => 'tech-2']);

    $slug = app(CategorySlugGenerator::class)->uniqueFor($user, 'Tech');

    expect($slug)->toBe('tech-3');
});

it('ignores the excluded category id when checking conflicts', function () {
    $user = User::factory()->create();
    $existing = Category::factory()->for($user)->create(['slug' => 'tech']);

    $slug = app(CategorySlugGenerator::class)->uniqueFor($user, 'Tech', exceptId: $existing->id);

    expect($slug)->toBe('tech');
});

it('scopes uniqueness per user', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    Category::factory()->for($other)->create(['slug' => 'tech']);

    $slug = app(CategorySlugGenerator::class)->uniqueFor($user, 'Tech');

    expect($slug)->toBe('tech');
});
