import { Area, AreaChart, CartesianGrid, XAxis, YAxis } from 'recharts';

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
import type { WeeklySaves } from '@/types';

/**
 * Single series, so no legend: the card title names the measure.
 */
const config = {
    saved: {
        label: 'Saved',
        color: 'var(--chart-1)',
    },
} satisfies ChartConfig;

const weekLabel = (week: string) =>
    new Date(week).toLocaleDateString('en', { month: 'short', day: 'numeric' });

export function SavesOverTimeChart({ weekly }: { weekly: WeeklySaves[] }) {
    const total = weekly.reduce((sum, point) => sum + point.saved, 0);

    return (
        <Card>
            <CardHeader>
                <CardTitle>Saves over time</CardTitle>
                <CardDescription>
                    {total} bookmarks over the last {weekly.length} weeks
                </CardDescription>
            </CardHeader>
            <CardContent>
                <ChartContainer config={config} className={`h-56 w-full`}>
                    <AreaChart
                        accessibilityLayer
                        data={weekly}
                        margin={{ left: 4, right: 8, top: 4 }}
                    >
                        <defs>
                            <linearGradient
                                id={`saves-fill`}
                                x1={`0`}
                                y1={`0`}
                                x2={`0`}
                                y2={`1`}
                            >
                                <stop
                                    offset={`5%`}
                                    stopColor={`var(--color-saved)`}
                                    stopOpacity={0.3}
                                />
                                <stop
                                    offset={`95%`}
                                    stopColor={`var(--color-saved)`}
                                    stopOpacity={0.02}
                                />
                            </linearGradient>
                        </defs>
                        <CartesianGrid vertical={false} strokeOpacity={0.4} />
                        <XAxis
                            dataKey={`week`}
                            tickLine={false}
                            axisLine={false}
                            tickMargin={8}
                            minTickGap={24}
                            tickFormatter={weekLabel}
                        />
                        <YAxis
                            tickLine={false}
                            axisLine={false}
                            width={28}
                            allowDecimals={false}
                        />
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
                            dataKey={`saved`}
                            type={`monotone`}
                            stroke={`var(--color-saved)`}
                            strokeWidth={2}
                            fill={`url(#saves-fill)`}
                        />
                    </AreaChart>
                </ChartContainer>
            </CardContent>
        </Card>
    );
}
