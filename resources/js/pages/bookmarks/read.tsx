import { Head, router, setLayoutProps, useHttp } from '@inertiajs/react';
import { ExternalLinkIcon } from 'lucide-react';
import { useEffect, useRef } from 'react';

import { BookmarkHeader } from '@/components/bookmark-header';
import { PluginSlot } from '@/components/plugin-slot';
import { useDebounce } from '@/hooks/use-debounce';
import { useScrollPercentage } from '@/hooks/use-scroll-percentage';
import { useTranslation } from '@/hooks/use-translation';
import bookmarks from '@/routes/bookmarks';
import type { Bookmark } from '@/types';

export default function BookmarkRead({ bookmark }: { bookmark: Bookmark }) {
    const { t } = useTranslation();

    /** `bookmark.title` è dato utente: entra grezzo, mai dentro `t()`. */
    setLayoutProps({
        breadcrumbs: [
            { title: t('Bookmarks'), href: bookmarks.index().url },
            {
                title: bookmark.title ?? t('Reader'),
                href: bookmarks.read(bookmark.id).url,
            },
        ],
    });

    const isPending = bookmark.status === 'pending';
    const hasContent =
        bookmark.content_html !== null && bookmark.content_html.length > 0;
    const shouldPoll =
        isPending ||
        (bookmark.status === 'parsed' &&
            !hasContent &&
            bookmark.content_text === null);

    const updateProgressHttp = useHttp({
        progress: bookmark.scroll_position,
    });

    const scrollPercentage = useScrollPercentage();
    const debouncedScrollPercentage = useDebounce(scrollPercentage, 1000);

    const isRestored = useRef(false);

    const scrollToPercentage = (percentage: number) => {
        if (percentage <= 0) {
            return;
        }

        const scrollHeight = document.documentElement.scrollHeight;
        const clientHeight = document.documentElement.clientHeight;
        const totalScrollableHeight = scrollHeight - clientHeight;
        const validatedPercent = Math.max(0, Math.min(percentage, 100));
        const scrollToPixel = (validatedPercent / 100) * totalScrollableHeight;

        window.scrollTo({
            top: scrollToPixel,
            behavior: 'smooth',
        });
    };

    useEffect(() => {
        updateProgressHttp.setData({
            progress: debouncedScrollPercentage,
        });
        // ponytail: updateProgressHttp omesso, la sua identità cambia a ogni
        // setData e includerlo farebbe un loop infinito
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [debouncedScrollPercentage]);

    useEffect(() => {
        if (
            !isRestored.current ||
            updateProgressHttp.data.progress === bookmark.reading_progress
        ) {
            return;
        }

        updateProgressHttp.patch(bookmarks.updateProgress(bookmark.id).url);
        // ponytail: reading_progress è il valore server di riferimento, non un
        // trigger; updateProgressHttp è instabile come sopra
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [bookmark.id, updateProgressHttp.data.progress]);

    useEffect(() => {
        // Restore scroll on initial load
        if (hasContent && !isRestored.current) {
            const timer = setTimeout(() => {
                scrollToPercentage(bookmark.scroll_position);
                isRestored.current = true;
            }, 100);

            return () => clearTimeout(timer);
        }
    }, [hasContent, bookmark.scroll_position]);

    useEffect(() => {
        if (!shouldPoll) {
            return;
        }

        const interval = setInterval(() => {
            router.reload({ only: ['bookmark'] });
        }, 3000);

        return () => clearInterval(interval);
    }, [shouldPoll]);

    return (
        <>
            <Head title={bookmark.title ?? t('Reader')} />

            <div className={`mx-auto max-w-3xl px-4 py-6`}>
                <BookmarkHeader bookmark={bookmark} isPending={isPending} />

                <PluginSlot
                    name={`bookmark-read-before-content`}
                    bookmark={bookmark}
                />

                {hasContent ? (
                    <article
                        className={`prose prose-lg max-w-none dark:prose-invert`}
                        dangerouslySetInnerHTML={{
                            __html: bookmark.content_html as string,
                        }}
                    />
                ) : isPending ? (
                    <div className={`flex flex-col gap-3`}>
                        {Array.from({ length: 8 }).map((_, i) => (
                            <div
                                key={i}
                                className={`h-4 animate-pulse rounded bg-muted`}
                                style={{ width: `${85 - (i % 4) * 10}%` }}
                            />
                        ))}
                        <p className={`mt-4 text-sm text-muted-foreground`}>
                            {t('Extracting article content…')}
                        </p>
                    </div>
                ) : (
                    <div
                        className={`rounded-lg border border-dashed p-8 text-center`}
                    >
                        <p className={`mb-4 text-muted-foreground`}>
                            {t(
                                "We couldn't extract readable content from this page.",
                            )}
                        </p>
                        <a
                            href={bookmark.url}
                            target={`_blank`}
                            rel={`noreferrer noopener`}
                            className={`inline-flex items-center gap-1 text-sm font-medium hover:underline`}
                        >
                            {t('Open original')}{' '}
                            <ExternalLinkIcon className={`size-4`} />
                        </a>
                    </div>
                )}
            </div>

            <PluginSlot name={`bookmark-read-aside`} bookmark={bookmark} />
        </>
    );
}
