<?php

use App\Models\Bookmark;
use App\Models\Category;
use App\Models\User;
use App\Services\Search\BookmarkSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

test('index without query falls back to standard listing', function () {
    $user = User::factory()->create();
    Bookmark::factory()->for($user)->count(3)->create(['status' => 'parsed']);

    $this->mock(BookmarkSearchService::class)
        ->shouldNotReceive('search');

    $response = $this->actingAs($user)->get(route('bookmarks.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('bookmarks/index')
        ->has('bookmarks.data', 3)
        ->where('q', null)
        ->where('highlights', [])
    );
});

test('index with q calls search service and exposes highlights', function () {
    $user = User::factory()->create();
    $matched = Bookmark::factory()->for($user)->create([
        'title' => 'Laravel Scout in depth',
        'status' => 'parsed',
        'content_text' => 'Indexing with meilisearch is fast.',
    ]);

    $highlights = [
        $matched->id => [
            'title' => '<mark>Laravel</mark> Scout in depth',
            'content_text' => 'Indexing with <mark>meilisearch</mark> is fast.',
        ],
    ];

    $this->mock(BookmarkSearchService::class, function (MockInterface $mock) use ($matched, $user, $highlights) {
        $paginator = new LengthAwarePaginator(
            new Collection([$matched->fresh()]),
            1,
            9,
            1,
            ['path' => route('bookmarks.index'), 'query' => ['q' => 'laravel']],
        );

        $mock->shouldReceive('search')
            ->once()
            ->withArgs(function (string $query, int $userId, ?int $categoryId) use ($user) {
                return $query === 'laravel' && $userId === $user->id && $categoryId === null;
            })
            ->andReturn(['paginator' => $paginator, 'highlights' => $highlights]);
    });

    $response = $this->actingAs($user)->get(route('bookmarks.index', ['q' => 'laravel']));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('bookmarks/index')
        ->where('q', 'laravel')
        ->has('bookmarks.data', 1)
        ->where('bookmarks.data.0.id', $matched->id)
        ->where('highlights.'.$matched->id.'.title', '<mark>Laravel</mark> Scout in depth')
    );
});

test('index with q and category passes the resolved category id to the search service', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['slug' => 'tech']);
    Bookmark::factory()->for($user)->for($category)->create(['status' => 'parsed']);

    $this->mock(BookmarkSearchService::class, function (MockInterface $mock) use ($user, $category) {
        $paginator = new LengthAwarePaginator(new Collection([]), 0, 9, 1);
        $mock->shouldReceive('search')
            ->once()
            ->withArgs(fn (string $q, int $uid, ?int $cid) => $q === 'foo' && $uid === $user->id && $cid === $category->id)
            ->andReturn(['paginator' => $paginator, 'highlights' => []]);
    });

    $this->actingAs($user)
        ->get(route('bookmarks.index', ['q' => 'foo', 'category' => 'tech']))
        ->assertOk();
});

test('q is trimmed and treated as empty when only whitespace', function () {
    $user = User::factory()->create();

    $this->mock(BookmarkSearchService::class)->shouldNotReceive('search');

    $this->actingAs($user)
        ->get(route('bookmarks.index', ['q' => '   ']))
        ->assertInertia(fn ($page) => $page->where('q', null));
});

test('q exceeding max length is rejected', function () {
    $user = User::factory()->create();
    $longQuery = str_repeat('a', 201);

    $this->actingAs($user)
        ->get(route('bookmarks.index', ['q' => $longQuery]))
        ->assertSessionHasErrors('q');
});
