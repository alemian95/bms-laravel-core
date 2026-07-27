<?php

namespace BmsCore\Packages\Ai\Services;

use App\Models\Bookmark;
use App\Models\User;
use BmsCore\Packages\Ai\Models\AiSummary;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Aggregates the figures the AI package contributes to the dashboard slot.
 *
 * Summaries carry no user of their own, so everything is scoped through the
 * bookmark they belong to.
 */
class AiDashboardStats
{
    private const WEEKS = 12;

    private const RECENT = 5;

    /**
     * @return array{
     *     summarized: int,
     *     bookmarks: int,
     *     recent: list<array{id: int, bookmarkId: int, title: string, createdAt: string}>,
     *     weekly: list<array{week: string, generated: int}>
     * }
     */
    public function for(User $user): array
    {
        return [
            'summarized' => $this->scoped($user)->count(),
            'bookmarks' => Bookmark::query()->where('user_id', $user->id)->count(),
            'recent' => $this->recent($user),
            'weekly' => $this->weekly($user),
        ];
    }

    /**
     * @return Builder<AiSummary>
     */
    private function scoped(User $user)
    {
        return AiSummary::query()
            ->whereHas('bookmark', fn ($query) => $query->where('user_id', $user->id));
    }

    /**
     * @return list<array{id: int, bookmarkId: int, title: string, createdAt: string}>
     */
    private function recent(User $user): array
    {
        /** @var Collection<int, AiSummary> $summaries */
        $summaries = $this->scoped($user)
            ->with('bookmark:id,title,url')
            ->orderByDesc('created_at')
            ->limit(self::RECENT)
            ->get(['id', 'bookmark_id', 'created_at']);

        return $summaries
            ->map(fn (AiSummary $summary): array => [
                'id' => (int) $summary->id,
                'bookmarkId' => (int) $summary->bookmark_id,
                'title' => $summary->bookmark->title ?? $summary->bookmark->url,
                'createdAt' => $summary->created_at->toDateString(),
            ])
            ->all();
    }

    /**
     * ponytail: same PHP bucketing as the core DashboardStats, same reason.
     *
     * @return list<array{week: string, generated: int}>
     */
    private function weekly(User $user): array
    {
        $start = CarbonImmutable::now()->startOfWeek()->subWeeks(self::WEEKS - 1);

        $generated = $this->scoped($user)
            ->where('created_at', '>=', $start)
            ->pluck('created_at')
            ->countBy(fn (CarbonInterface $date) => $date->startOfWeek()->toDateString());

        return collect(range(0, self::WEEKS - 1))
            ->map(function (int $offset) use ($start, $generated): array {
                $week = $start->addWeeks($offset)->toDateString();

                return ['week' => $week, 'generated' => $generated->get($week, 0)];
            })
            ->all();
    }
}
