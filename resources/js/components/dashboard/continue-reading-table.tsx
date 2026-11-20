import { Link } from '@inertiajs/react';

import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useTranslation } from '@/hooks/use-translation';
import bookmarks from '@/routes/bookmarks';
import type { ContinueReadingItem } from '@/types';

export function ContinueReadingTable({
    items,
}: {
    items: ContinueReadingItem[];
}) {
    const { t } = useTranslation();

    return (
        <Card>
            <CardHeader>
                <CardTitle>{t('Continue reading')}</CardTitle>
                <CardDescription>
                    {t("Articles you started but haven't finished")}
                </CardDescription>
            </CardHeader>
            <CardContent>
                {items.length === 0 ? (
                    <p className={`py-8 text-sm text-muted-foreground`}>
                        {t(
                            'Nothing in progress. Open a bookmark to start reading.',
                        )}
                    </p>
                ) : (
                    <Table>
                        <TableHeader>
                            <TableRow className={`text-xs`}>
                                <TableHead>{t('Article')}</TableHead>
                                <TableHead className={`hidden sm:table-cell`}>
                                    {t('Category')}
                                </TableHead>
                                <TableHead className={`w-32 text-right`}>
                                    {t('Progress')}
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {items.map((item) => (
                                <TableRow key={item.id}>
                                    <TableCell className={`whitespace-normal`}>
                                        <Link
                                            href={bookmarks.read(item.id).url}
                                            className={`line-clamp-1 font-medium hover:underline`}
                                        >
                                            {item.title}
                                        </Link>
                                        {item.domain ? (
                                            <span
                                                className={`block text-xs text-muted-foreground`}
                                            >
                                                {item.domain}
                                            </span>
                                        ) : null}
                                    </TableCell>
                                    <TableCell
                                        className={`hidden sm:table-cell`}
                                    >
                                        {item.category ? (
                                            <span
                                                className={`inline-flex items-center gap-1.5 text-muted-foreground`}
                                            >
                                                <span
                                                    className={`size-2 shrink-0 rounded-full`}
                                                    style={{
                                                        backgroundColor:
                                                            item.categoryColor ??
                                                            'var(--muted-foreground)',
                                                    }}
                                                    aria-hidden
                                                />
                                                {item.category}
                                            </span>
                                        ) : (
                                            <span
                                                className={`text-muted-foreground`}
                                            >
                                                —
                                            </span>
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        <div
                                            className={`flex items-center justify-end gap-2`}
                                        >
                                            <Progress
                                                value={item.progress}
                                                className={`h-1.5 w-16 bg-muted [&>div]:bg-[var(--chart-1)]`}
                                                aria-label={t('Progress')}
                                            />
                                            <span
                                                className={`w-9 text-right text-muted-foreground tabular-nums`}
                                            >
                                                {item.progress}%
                                            </span>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                )}
            </CardContent>
        </Card>
    );
}
