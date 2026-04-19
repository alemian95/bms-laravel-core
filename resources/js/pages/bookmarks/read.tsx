import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeftIcon, ExternalLinkIcon } from 'lucide-react';
import { useEffect } from 'react';

import bookmarks from '@/routes/bookmarks';
import type { Bookmark } from '@/types';

export default function BookmarkRead({ bookmark }: { bookmark: Bookmark }) {
    const isPending = bookmark.status === 'pending';
    const hasContent = bookmark.content_html !== null && bookmark.content_html.length > 0;
    const shouldPoll = isPending || (bookmark.status === 'parsed' && !hasContent && bookmark.content_text === null);

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
            <Head title={bookmark.title ?? 'Reader'} />

            <div className={`mx-auto max-w-3xl px-4 py-6`}>
                <Link
                    href={bookmarks.index().url}
                    className={`mb-6 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground`}
                >
                    <ArrowLeftIcon className={`size-4`} /> Back to bookmarks
                </Link>

                <header className={`mb-8 border-b pb-6`}>
                    {isPending ? (
                        <>
                            <div className={`mb-3 h-8 w-3/4 animate-pulse rounded bg-muted`} />
                            <div className={`h-4 w-1/3 animate-pulse rounded bg-muted`} />
                        </>
                    ) : (
                        <>
                            <h1 className={`mb-3 text-3xl font-bold leading-tight`}>
                                {bookmark.title ?? bookmark.url}
                            </h1>
                            <div className={`flex flex-wrap items-center gap-3 text-sm text-muted-foreground`}>
                                {bookmark.domain && <span>{bookmark.domain}</span>}
                                {bookmark.author && <span>· {bookmark.author}</span>}
                                {bookmark.category && (
                                    <span
                                        className={`rounded-full px-2 py-0.5 text-xs font-medium`}
                                        style={{ backgroundColor: (bookmark.category.color ?? '#000') + '22', color: bookmark.category.color ?? undefined }}
                                    >
                                        {bookmark.category.name}
                                    </span>
                                )}
                                <a
                                    href={bookmark.url}
                                    target={`_blank`}
                                    rel={`noreferrer noopener`}
                                    className={`ml-auto inline-flex items-center gap-1 hover:text-foreground`}
                                >
                                    Open original <ExternalLinkIcon className={`size-3`} />
                                </a>
                            </div>
                        </>
                    )}
                </header>

                {hasContent ? (
                    <article
                        className={`prose prose-lg dark:prose-invert max-w-none`}
                        dangerouslySetInnerHTML={{ __html: bookmark.content_html as string }}
                    />
                ) : isPending ? (
                    <div className={`flex flex-col gap-3`}>
                        {Array.from({ length: 8 }).map((_, i) => (
                            <div key={i} className={`h-4 animate-pulse rounded bg-muted`} style={{ width: `${85 - (i % 4) * 10}%` }} />
                        ))}
                        <p className={`mt-4 text-sm text-muted-foreground`}>Extracting article content…</p>
                    </div>
                ) : (
                    <div className={`rounded-lg border border-dashed p-8 text-center`}>
                        <p className={`mb-4 text-muted-foreground`}>
                            We couldn't extract readable content from this page.
                        </p>
                        <a
                            href={bookmark.url}
                            target={`_blank`}
                            rel={`noreferrer noopener`}
                            className={`inline-flex items-center gap-1 text-sm font-medium hover:underline`}
                        >
                            Open original <ExternalLinkIcon className={`size-4`} />
                        </a>
                    </div>
                )}
            </div>
        </>
    );
}

BookmarkRead.layout = ({ bookmark }: { bookmark: Bookmark }) => ({
    breadcrumbs: [
        { title: 'Bookmarks', href: bookmarks.index().url },
        { title: bookmark.title ?? 'Reader', href: bookmarks.read(bookmark.id).url },
    ],
});
