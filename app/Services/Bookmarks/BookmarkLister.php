<?php

namespace App\Services\Bookmarks;

use App\Data\Bookmarks\ListBookmarksFilters;
use App\Models\Bookmark;
use App\Models\Category;
use App\Models\User;
use App\Services\Search\BookmarkSearchService;
use Illuminate\Pagination\LengthAwarePaginator;

class BookmarkLister
{
    public function __construct(
        private BookmarkSearchService $search,
    ) {}

    /**
     * @return array{paginator: LengthAwarePaginator, highlights: array<int, array{title?: string, content_text?: string}>, activeCategory: ?Category}
     */
    public function list(User $user, ListBookmarksFilters $filters): array
    {
        $activeCategory = $filters->categorySlug
            ? Category::where('user_id', $user->id)->where('slug', $filters->categorySlug)->first()
            : null;

        $query = trim((string) ($filters->query ?? ''));

        if ($query !== '') {
            $result = $this->search->search(
                query: $query,
                userId: $user->id,
                categoryId: $activeCategory?->id,
                perPage: $filters->perPage,
                page: $filters->page,
                path: $filters->path,
                queryParams: $filters->queryParams,
            );

            return [
                'paginator' => $result['paginator'],
                'highlights' => $result['highlights'],
                'activeCategory' => $activeCategory,
            ];
        }

        $paginator = Bookmark::query()
            ->where('user_id', $user->id)
            ->with('category:id,name,slug,color')
            ->when($activeCategory, fn ($q) => $q->where('category_id', $activeCategory->id))
            ->orderByDesc('created_at')
            ->paginate($filters->perPage, ['*'], 'page', $filters->page)
            ->withQueryString();

        return [
            'paginator' => $paginator,
            'highlights' => [],
            'activeCategory' => $activeCategory,
        ];
    }
}
