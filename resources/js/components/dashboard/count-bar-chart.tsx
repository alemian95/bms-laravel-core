import { Bar, BarChart, LabelList, XAxis, YAxis } from 'recharts';

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
import { useTranslation } from '@/hooks/use-translation';

export type CountBar = {
    label: string;
    value: number;
};

/**
 * Horizontal bars for a single count series. One color for every bar on
 * purpose: the axis label already carries the identity, so per-bar hues would
 * only repeat it. Values are printed at the bar end, which also covers the
 * contrast shortfall of `--chart-1` against the dark surface.
 */
export function CountBarChart({
    title,
    description,
    data,
    emptyMessage,
}: {
    title: string;
    description?: string;
    data: CountBar[];
    emptyMessage?: string;
}) {
    const { t } = useTranslation();

    const config = {
        value: {
            label: t('Bookmarks'),
            color: 'var(--chart-1)',
        },
    } satisfies ChartConfig;

    return (
        <Card>
            <CardHeader>
                <CardTitle>{title}</CardTitle>
                {description ? (
                    <CardDescription>{description}</CardDescription>
                ) : null}
            </CardHeader>
            <CardContent>
                {data.length === 0 ? (
                    <p className={`py-8 text-sm text-muted-foreground`}>
                        {emptyMessage ?? t('Nothing to show yet.')}
                    </p>
                ) : (
                    <ChartContainer
                        config={config}
                        className={`w-full`}
                        style={{ height: `${data.length * 32 + 16}px` }}
                    >
                        <BarChart
                            accessibilityLayer
                            data={data}
                            layout={`vertical`}
                            barCategoryGap={2}
                            margin={{ left: 0, right: 32 }}
                        >
                            <XAxis type={`number`} hide />
                            <YAxis
                                dataKey={`label`}
                                type={`category`}
                                tickLine={false}
                                axisLine={false}
                                width={110}
                                tickMargin={8}
                            />
                            <ChartTooltip
                                cursor={false}
                                content={
                                    <ChartTooltipContent
                                        hideIndicator
                                        hideLabel
                                    />
                                }
                            />
                            <Bar
                                dataKey={`value`}
                                fill={`var(--color-value)`}
                                radius={[0, 4, 4, 0]}
                                barSize={18}
                            >
                                <LabelList
                                    dataKey={`value`}
                                    position={`right`}
                                    offset={8}
                                    className={`fill-muted-foreground`}
                                    fontSize={12}
                                />
                            </Bar>
                        </BarChart>
                    </ChartContainer>
                )}
            </CardContent>
        </Card>
    );
}
