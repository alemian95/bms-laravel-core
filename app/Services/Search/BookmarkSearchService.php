<?php

namespace App\Services\Search;

use App\Models\Bookmark;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Meilisearch\Client;

class BookmarkSearchService
{
    public function __construct(private Client $client) {}

    /**
     * @return array{paginator: LengthAwarePaginator, highlights: array<int, array{title?: string, content_text?: string}>}
     */
    public function search(
        string $query,
        int $userId,
        ?int $categoryId,
        int $perPage,
        int $page,
        string $path,
        array $queryParams,
    ): array {
        $filters = ["user_id = {$userId}", "status = 'parsed'"];
        if ($categoryId !== null) {
            $filters[] = "category_id = {$categoryId}";
        }

        $result = $this->client->index('bookmarks')->search($query, [
            'filter' => implode(' AND ', $filters),
            'attributesToHighlight' => ['title', 'content_text'],
            'attributesToCrop' => ['content_text:40'],
            'highlightPreTag' => '<mark>',
            'highlightPostTag' => '</mark>',
            'page' => $page,
            'hitsPerPage' => $perPage,
        ]);

        $hits = $result->getHits();
        $ids = array_map(fn (array $hit) => (int) $hit['id'], $hits);

        $models = Bookmark::query()
            ->whereIn('id', $ids)
            ->with('category:id,name,slug,color')
            ->get()
            ->keyBy('id');

        /** @var Collection<int, Bookmark> $ordered */
        $ordered = collect($ids)
            ->map(fn (int $id) => $models->get($id))
            ->filter()
            ->values();

        $highlights = [];
        foreach ($hits as $hit) {
            $formatted = $hit['_formatted'] ?? [];
            $highlights[(int) $hit['id']] = array_filter([
                'title' => $formatted['title'] ?? null,
                'content_text' => $formatted['content_text'] ?? null,
            ], fn ($v) => $v !== null && $v !== '');
        }

        $paginator = new LengthAwarePaginator(
            $ordered,
            $result->getTotalHits() ?? count($hits),
            $perPage,
            $page,
            ['path' => $path, 'query' => $queryParams],
        );

        return ['paginator' => $paginator, 'highlights' => $highlights];
    }
}
