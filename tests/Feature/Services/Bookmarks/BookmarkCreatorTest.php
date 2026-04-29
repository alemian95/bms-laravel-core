<?php

use App\Data\Bookmarks\CreateBookmarkData;
use App\Exceptions\Bookmarks\CategoryNotOwnedException;
use App\Exceptions\Bookmarks\DuplicateBookmarkException;
use App\Jobs\ExtractBookmarkMetadataJob;
use App\Jobs\ParseArticleContentJob;
use App\Models\Bookmark;
use App\Models\Category;
use App\Models\User;
use App\Services\Bookmarks\BookmarkCreator;
use Illuminate\Support\Facades\Bus;

it('creates a bookmark with pending status and dispatches job chain', function () {
    Bus::fake();
    $user = User::factory()->create();

    $bookmark = app(BookmarkCreator::class)->create(
        $user,
        new CreateBookmarkData(url: 'https://example.com/article'),
    );

    expect($bookmark)->toBeInstanceOf(Bookmark::class)
        ->and($bookmark->user_id)->toBe($user->id)
        ->and($bookmark->url)->toBe('https://example.com/article')
        ->and($bookmark->status)->toBe('pending')
        ->and($bookmark->category_id)->toBeNull();

    Bus::assertChained([
        fn (ExtractBookmarkMetadataJob $job) => $job->bookmark->is($bookmark),
        fn (ParseArticleContentJob $job) => $job->bookmark->is($bookmark),
    ]);
});

it('normalizes the url before persisting', function () {
    Bus::fake();
    $user = User::factory()->create();

    $bookmark = app(BookmarkCreator::class)->create(
        $user,
        new CreateBookmarkData(url: 'https://example.com/article#heading'),
    );

    expect($bookmark->url)->toBe('https://example.com/article');
});

it('attaches the category when owned by the user', function () {
    Bus::fake();
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create();

    $bookmark = app(BookmarkCreator::class)->create(
        $user,
        new CreateBookmarkData(url: 'https://example.com/x', categoryId: $category->id),
    );

    expect($bookmark->category_id)->toBe($category->id);
});

it('throws CategoryNotOwnedException when the category belongs to another user', function () {
    Bus::fake();
    $user = User::factory()->create();
    $other = User::factory()->create();
    $category = Category::factory()->for($other)->create();

    expect(fn () => app(BookmarkCreator::class)->create(
        $user,
        new CreateBookmarkData(url: 'https://example.com/x', categoryId: $category->id),
    ))->toThrow(CategoryNotOwnedException::class);

    expect(Bookmark::count())->toBe(0);
    Bus::assertNothingDispatched();
});

it('throws DuplicateBookmarkException when the same user already saved the url', function () {
    Bus::fake();
    $user = User::factory()->create();
    Bookmark::factory()->for($user)->create(['url' => 'https://example.com/dup']);

    expect(fn () => app(BookmarkCreator::class)->create(
        $user,
        new CreateBookmarkData(url: 'https://example.com/dup'),
    ))->toThrow(DuplicateBookmarkException::class);

    expect(Bookmark::where('user_id', $user->id)->count())->toBe(1);
    Bus::assertNothingDispatched();
});

it('treats fragmented duplicate as duplicate after normalization', function () {
    Bus::fake();
    $user = User::factory()->create();
    Bookmark::factory()->for($user)->create(['url' => 'https://example.com/dup']);

    expect(fn () => app(BookmarkCreator::class)->create(
        $user,
        new CreateBookmarkData(url: 'https://example.com/dup#section'),
    ))->toThrow(DuplicateBookmarkException::class);
});
