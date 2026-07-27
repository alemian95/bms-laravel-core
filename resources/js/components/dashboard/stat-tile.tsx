import type { LucideIcon } from 'lucide-react';

import { Card, CardContent } from '@/components/ui/card';

/**
 * A single headline figure. Deliberately not a chart: one number needs no plot,
 * and the label carries the meaning rather than a color.
 */
export function StatTile({
    label,
    value,
    icon: Icon,
}: {
    label: string;
    value: number;
    icon: LucideIcon;
}) {
    return (
        <Card>
            <CardContent className={`flex items-center justify-between gap-4`}>
                <div>
                    <p className={`text-sm text-muted-foreground`}>{label}</p>
                    <p
                        className={`mt-1 text-3xl font-semibold tracking-tight tabular-nums`}
                    >
                        {value}
                    </p>
                </div>
                <Icon
                    className={`size-5 shrink-0 text-muted-foreground`}
                    aria-hidden
                />
            </CardContent>
        </Card>
    );
}
