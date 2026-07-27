<?php

namespace App\Services\Dashboard;

use App\Models\Bookmark;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Aggregates the read-only figures the dashboard renders.
 *
 * Every query is scoped to the given user; nothing here mutates state.
 */
class DashboardStats
{
    private const WEEKS = 12;

    private const TOP_DOMAINS = 8;

    private const CONTINUE_READING = 5;

    /**
     * @return array{
     *     counters: array{total: int, unread: int, inProgress: int, completed: int},
     *     weekly: list<array{week: string, saved: int}>,
     *     byCategory: list<array{name: string, total: int}>,
     *     topDomains: list<array{domain: string, total: int}>,
     *     continueReading: list<array{id: int, title: string, domain: string|null, progress: int, category: string|null, categoryColor: string|null}>
     * }
     */
    public function for(User $user): array
    {
        return [
            'counters' => $this->counters($user),
            'weekly' => $this->weekly($user),
            'byCategory' => $this->byCategory($user),
            'topDomains' => $this->topDomains($user),
            'continueReading' => $this->continueReading($user),
        ];
    }

    /**
     * @return array{total: int, unread: int, inProgress: int, completed: int}
     */
    private function counters(User $user): array
    {
        /** @var object{total: int, unread: int, in_progress: int, completed: int}|null $row */
        $row = Bookmark::query()
            ->where('user_id', $user->id)
            ->selectRaw('count(*) as total')
            ->selectRaw('count(*) filter (where reading_progress <= 0) as unread')
            ->selectRaw('count(*) filter (where reading_progress between 1 and 99) as in_progress')
            ->selectRaw('count(*) filter (where reading_progress >= 100) as completed')
            ->first();

        return [
            'total' => (int) ($row->total ?? 0),
            'unread' => (int) ($row->unread ?? 0),
            'inProgress' => (int) ($row->in_progress ?? 0),
            'completed' => (int) ($row->completed ?? 0),
        ];
    }

    /**
     * Saves per week for the last 12 weeks, including the weeks with none.
     *
     * ponytail: buckets in PHP instead of SQL so it stays driver-agnostic.
     * Move to date_trunc if a user ever saves enough to make this row set big.
     *
     * @return list<array{week: string, saved: int}>
     */
    private function weekly(User $user): array
    {
        $start = CarbonImmutable::now()->startOfWeek()->subWeeks(self::WEEKS - 1);

        $saved = Bookmark::query()
            ->where('user_id', $user->id)
            ->where('created_at', '>=', $start)
            ->pluck('created_at')
            ->countBy(fn (CarbonInterface $date) => $date->startOfWeek()->toDateString());

        return collect(range(0, self::WEEKS - 1))
            ->map(function (int $offset) use ($start, $saved): array {
                $week = $start->addWeeks($offset)->toDateString();

                return ['week' => $week, 'saved' => $saved->get($week, 0)];
            })
            ->all();
    }

    /**
     * @return list<array{name: string, total: int}>
     */
    private function byCategory(User $user): array
    {
        return Bookmark::query()
            ->where('bookmarks.user_id', $user->id)
            ->leftJoin('categories', 'bookmarks.category_id', '=', 'categories.id')
            ->selectRaw('categories.name as name, count(*) as total')
            ->groupBy('categories.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn (Bookmark $row): array => [
                'name' => $row->getAttribute('name') ?? 'Uncategorized',
                'total' => (int) $row->getAttribute('total'),
            ])
            ->all();
    }

    /**
     * Top domains, with everything past the cutoff folded into a single "Other".
     *
     * @return list<array{domain: string, total: int}>
     */
    private function topDomains(User $user): array
    {
        $domains = Bookmark::query()
            ->where('user_id', $user->id)
            ->whereNotNull('domain')
            ->selectRaw('domain, count(*) as total')
            ->groupBy('domain')
            ->orderByDesc('total')
            ->get()
            ->map(fn (Bookmark $row): array => [
                'domain' => (string) $row->getAttribute('domain'),
                'total' => (int) $row->getAttribute('total'),
            ]);

        $top = $domains->take(self::TOP_DOMAINS);
        $rest = $domains->skip(self::TOP_DOMAINS);

        return $rest->isEmpty()
            ? $top->all()
            : $top->push(['domain' => 'Other', 'total' => (int) $rest->sum('total')])->all();
    }

    /**
     * @return list<array{id: int, title: string, domain: string|null, progress: int, category: string|null, categoryColor: string|null}>
     */
    private function continueReading(User $user): array
    {
        /** @var Collection<int, Bookmark> $bookmarks */
        $bookmarks = Bookmark::query()
            ->where('user_id', $user->id)
            ->whereBetween('reading_progress', [1, 99])
            ->with('category:id,name,color')
            ->orderByDesc('updated_at')
            ->limit(self::CONTINUE_READING)
            ->get(['id', 'title', 'url', 'domain', 'reading_progress', 'category_id', 'updated_at']);

        return $bookmarks
            ->map(fn (Bookmark $bookmark): array => [
                'id' => (int) $bookmark->id,
                'title' => $bookmark->title ?? $bookmark->url,
                'domain' => $bookmark->domain,
                'progress' => (int) $bookmark->reading_progress,
                'category' => $bookmark->category?->name,
                'categoryColor' => $bookmark->category?->color,
            ])
            ->all();
    }
}
