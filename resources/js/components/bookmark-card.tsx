import { Link, router } from '@inertiajs/react';
import { AlertTriangleIcon, BookOpenIcon, ExternalLinkIcon, Trash2Icon } from 'lucide-react';

import bookmarks from '@/routes/bookmarks';
import type { Bookmark } from '@/types';

export function BookmarkCard({ bookmark }: { bookmark: Bookmark }) {
    const isPending = bookmark.status === 'pending';
    const isFailed = bookmark.status === 'failed';

    const handleDelete = () => {
        if (confirm('Are you sure you want to delete this bookmark?')) {
            router.delete(bookmarks.destroy(bookmark.id), { preserveScroll: true });
        }
    };

    return (
        <article className={`group flex flex-col overflow-hidden rounded-lg border bg-card shadow-xs transition hover:shadow-md`}>
            <div className={`relative aspect-[16/9] w-full overflow-hidden bg-muted`}>
                {isPending ? (
                    <div className={`size-full animate-pulse bg-muted`} />
                ) : bookmark.thumbnail_url ? (
                    <img
                        src={bookmark.thumbnail_url}
                        alt={bookmark.title ?? ''}
                        className={`size-full object-cover`}
                        loading={`lazy`}
                        onError={(e) => {
                            (e.currentTarget as HTMLImageElement).style.display = 'none';
                        }}
                    />
                ) : (
                    <div className={`flex size-full items-center justify-center text-xs text-muted-foreground`}>
                        No preview
                    </div>
                )}

                {isFailed && (
                    <div className={`absolute top-2 right-2 flex items-center gap-1 rounded-full bg-destructive px-2 py-0.5 text-xs text-destructive-foreground`}>
                        <AlertTriangleIcon className={`size-3`} /> Failed
                    </div>
                )}

                {bookmark.category && (
                    <div
                        className={`absolute bottom-2 left-2 rounded-full px-2 py-0.5 text-xs font-medium`}
                        style={{ backgroundColor: (bookmark.category.color ?? '#000') + 'cc', color: '#fff' }}
                    >
                        {bookmark.category.name}
                    </div>
                )}
            </div>

            <div className={`flex flex-1 flex-col gap-2 p-4`}>
                {isPending ? (
                    <>
                        <div className={`h-4 w-3/4 animate-pulse rounded bg-muted`} />
                        <div className={`h-3 w-1/2 animate-pulse rounded bg-muted`} />
                    </>
                ) : (
                    <>
                        <h3 className={`line-clamp-2 font-semibold leading-tight`}>
                            {bookmark.title ?? bookmark.url}
                        </h3>
                        <div className={`text-xs text-muted-foreground`}>
                            {bookmark.domain}
                            {bookmark.author && <> · {bookmark.author}</>}
                        </div>
                    </>
                )}

                <div className={`mt-auto flex items-center justify-between pt-3`}>
                    <div className={`flex items-center gap-3`}>
                        {!isPending && (
                            <Link
                                href={bookmarks.read(bookmark.id).url}
                                className={`inline-flex items-center gap-1 text-xs font-medium text-foreground hover:underline`}
                            >
                                <BookOpenIcon className={`size-3`} /> Read
                            </Link>
                        )}
                        <a
                            href={bookmark.url}
                            target={`_blank`}
                            rel={`noreferrer noopener`}
                            className={`inline-flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground`}
                        >
                            Open <ExternalLinkIcon className={`size-3`} />
                        </a>
                    </div>
                    <button
                        onClick={handleDelete}
                        className={`cursor-pointer text-muted-foreground hover:text-destructive`}
                        aria-label={`Delete bookmark`}
                    >
                        <Trash2Icon className={`size-4`} />
                    </button>
                </div>
            </div>
        </article>
    );
}
