import { Link } from '@inertiajs/react';
import { ArrowLeftIcon, ExternalLinkIcon } from 'lucide-react';
import { useTranslation } from '@/hooks/use-translation';
import bookmarks from '@/routes/bookmarks';
import type { Bookmark } from '@/types';

export function BookmarkHeader({
    bookmark,
    isPending,
}: {
    bookmark: Bookmark;
    isPending: boolean;
}) {
    const { t } = useTranslation();

    return (
        <>
            <Link
                href={bookmarks.index().url}
                className={`mb-6 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground`}
            >
                <ArrowLeftIcon className={`size-4`} /> {t('Back to bookmarks')}
            </Link>

            <header className={`mb-8 border-b pb-6`}>
                {isPending ? (
                    <>
                        <div
                            className={`mb-3 h-8 w-3/4 animate-pulse rounded bg-muted`}
                        />
                        <div
                            className={`h-4 w-1/3 animate-pulse rounded bg-muted`}
                        />
                    </>
                ) : (
                    <>
                        <h1 className={`mb-3 text-3xl leading-tight font-bold`}>
                            {bookmark.title ?? bookmark.url}
                        </h1>

                        {bookmark.url != null &&
                            true &&
                            bookmark.title != null &&
                            true && (
                                <img
                                    src={bookmark.thumbnail_url!}
                                    alt={bookmark.title!}
                                />
                            )}

                        <div
                            className={`flex flex-wrap items-center gap-3 text-sm text-muted-foreground`}
                        >
                            {bookmark.domain && <span>{bookmark.domain}</span>}
                            {bookmark.author && (
                                <span>· {bookmark.author}</span>
                            )}
                            {bookmark.category && (
                                <span
                                    className={`rounded-full px-2 py-0.5 text-xs font-medium`}
                                    style={{
                                        backgroundColor:
                                            (bookmark.category.color ??
                                                '#000') + '22',
                                        color:
                                            bookmark.category.color ??
                                            undefined,
                                    }}
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
                                {t('Open original')}{' '}
                                <ExternalLinkIcon className={`size-3`} />
                            </a>
                        </div>
                    </>
                )}
            </header>
        </>
    );
}
