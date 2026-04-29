<?php

use App\Data\Bookmarks\ListBookmarksFilters;
use App\Models\Bookmark;
use App\Models\Category;
use App\Models\User;
use App\Services\Bookmarks\BookmarkLister;

it('lists current user bookmarks paginated', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    Bookmark::factory()->for($user)->count(3)->create();
    Bookmark::factory()->for($other)->count(5)->create();

    $result = app(BookmarkLister::class)->list($user, new ListBookmarksFilters);

    expect($result['paginator']->total())->toBe(3)
        ->and($result['highlights'])->toBe([])
        ->and($result['activeCategory'])->toBeNull();
});

it('filters by active category slug', function () {
    $user = User::factory()->create();
    $tech = Category::factory()->for($user)->create(['slug' => 'tech']);
    $news = Category::factory()->for($user)->create(['slug' => 'news']);

    Bookmark::factory()->for($user)->for($tech)->count(2)->create();
    Bookmark::factory()->for($user)->for($news)->count(4)->create();

    $result = app(BookmarkLister::class)->list(
        $user,
        new ListBookmarksFilters(categorySlug: 'tech'),
    );

    expect($result['paginator']->total())->toBe(2)
        ->and($result['activeCategory']?->slug)->toBe('tech');
});

it('returns null active category when slug is unknown', function () {
    $user = User::factory()->create();

    $result = app(BookmarkLister::class)->list(
        $user,
        new ListBookmarksFilters(categorySlug: 'does-not-exist'),
    );

    expect($result['activeCategory'])->toBeNull()
        ->and($result['paginator']->total())->toBe(0);
});
