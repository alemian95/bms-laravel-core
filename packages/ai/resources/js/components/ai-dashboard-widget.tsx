import { Link, usePage } from '@inertiajs/react';
import { SparklesIcon } from 'lucide-react';
import { Area, AreaChart } from 'recharts';

import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    ChartContainer,
    ChartTooltip,
    ChartTooltipContent,
} from '@/components/ui/chart';
import type { ChartConfig } from '@/components/ui/chart';
import ai from '@/routes/ai';

type AiStats = {
    summarized: number;
    bookmarks: number;
    recent: {
        id: number;
        bookmarkId: number;
        title: string;
        createdAt: string;
    }[];
    weekly: { week: string; generated: number }[];
};

/** Distinct from the core charts: summaries are a different entity. */
const config = {
    generated: {
        label: 'Summaries',
        color: 'var(--chart-2)',
    },
} satisfies ChartConfig;

const weekLabel = (week: string) =>
    new Date(week).toLocaleDateString('en', { month: 'short', day: 'numeric' });

export default function AiDashboardWidget() {
    const stats = usePage().props.aiStats as AiStats | null;

    if (!stats) {
        return null;
    }

    const coverage =
        stats.bookmarks > 0
            ? Math.round((stats.summarized / stats.bookmarks) * 100)
            : 0;

    return (
        <Card>
            <CardHeader>
                <CardTitle className={`flex items-center gap-2`}>
                    <SparklesIcon className={`size-4`} aria-hidden />
                    AI summaries
                </CardTitle>
                <CardDescription>
                    {stats.summarized} of {stats.bookmarks} bookmarks summarized
                </CardDescription>
            </CardHeader>
            <CardContent className={`grid gap-6 lg:grid-cols-2`}>
                <div className={`flex flex-col gap-4`}>
                    <div>
                        <div
                            className={`mb-2 flex items-baseline justify-between`}
                        >
                            <span className={`text-sm text-muted-foreground`}>
                                Coverage
                            </span>
                            <span
                                className={`text-2xl font-semibold tabular-nums`}
                            >
                                {coverage}%
                            </span>
                        </div>
                        <div
                            className={`h-2 overflow-hidden rounded-full bg-muted`}
                            role={`meter`}
                            aria-valuenow={coverage}
                            aria-valuemin={0}
                            aria-valuemax={100}
                            aria-label={`Summary coverage`}
                        >
                            <div
                                className={`h-full rounded-full bg-[var(--chart-2)]`}
                                style={{ width: `${coverage}%` }}
                            />
                        </div>
                    </div>

                    <ChartContainer config={config} className={`h-20 w-full`}>
                        <AreaChart
                            accessibilityLayer
                            data={stats.weekly}
                            margin={{ top: 4, bottom: 0, left: 0, right: 0 }}
                        >
                            <ChartTooltip
                                cursor={{ strokeDasharray: '4 4' }}
                                content={
                                    <ChartTooltipContent
                                        labelFormatter={(value) =>
                                            `Week of ${weekLabel(String(value))}`
                                        }
                                        indicator={`line`}
                                    />
                                }
                            />
                            <Area
                                dataKey={`generated`}
                                type={`monotone`}
                                stroke={`var(--color-generated)`}
                                strokeWidth={2}
                                fill={`var(--color-generated)`}
                                fillOpacity={0.15}
                            />
                        </AreaChart>
                    </ChartContainer>
                    <p className={`-mt-2 text-xs text-muted-foreground`}>
                        Generated per week, last {stats.weekly.length} weeks
                    </p>
                </div>

                <div>
                    <p className={`mb-2 text-sm text-muted-foreground`}>
                        Latest summaries
                    </p>
                    {stats.recent.length === 0 ? (
                        <p className={`text-sm text-muted-foreground`}>
                            No summaries yet.
                        </p>
                    ) : (
                        <ul className={`divide-y`}>
                            {stats.recent.map((summary) => (
                                <li
                                    key={summary.id}
                                    className={`flex items-baseline justify-between gap-4 py-2`}
                                >
                                    <Link
                                        href={
                                            ai.summary(summary.bookmarkId).url
                                        }
                                        className={`line-clamp-1 text-sm hover:underline`}
                                    >
                                        {summary.title}
                                    </Link>
                                    <span
                                        className={`shrink-0 text-xs text-muted-foreground`}
                                    >
                                        {weekLabel(summary.createdAt)}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}
