import { Link } from '@inertiajs/react';

import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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
                    <div className={`overflow-x-auto`}>
                        <table className={`w-full text-sm`}>
                            <thead>
                                <tr
                                    className={`border-b text-left text-xs text-muted-foreground`}
                                >
                                    <th className={`pb-2 font-medium`}>
                                        {t('Article')}
                                    </th>
                                    <th
                                        className={`hidden pb-2 font-medium sm:table-cell`}
                                    >
                                        {t('Category')}
                                    </th>
                                    <th
                                        className={`w-32 pb-2 text-right font-medium`}
                                    >
                                        {t('Progress')}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {items.map((item) => (
                                    <tr
                                        key={item.id}
                                        className={`border-b last:border-0`}
                                    >
                                        <td className={`py-3 pr-4`}>
                                            <Link
                                                href={
                                                    bookmarks.read(item.id).url
                                                }
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
                                        </td>
                                        <td
                                            className={`hidden py-3 pr-4 sm:table-cell`}
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
                                        </td>
                                        <td className={`py-3`}>
                                            <div
                                                className={`flex items-center justify-end gap-2`}
                                            >
                                                <div
                                                    className={`h-1.5 w-16 overflow-hidden rounded-full bg-muted`}
                                                >
                                                    <div
                                                        className={`h-full rounded-full bg-[var(--chart-1)]`}
                                                        style={{
                                                            width: `${item.progress}%`,
                                                        }}
                                                    />
                                                </div>
                                                <span
                                                    className={`w-9 text-right text-muted-foreground tabular-nums`}
                                                >
                                                    {item.progress}%
                                                </span>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
