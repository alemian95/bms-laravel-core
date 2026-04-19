import { Head, Link, router } from '@inertiajs/react';
import { useEffect } from 'react';

import { BookmarkCard } from '@/components/bookmark-card';
import { NewBookmarkDialog } from '@/components/new-bookmark-dialog';
import bookmarks from '@/routes/bookmarks';
import type { Bookmark, Category } from '@/types';

export default function BookmarksIndex({
    bookmarks: items,
    categories,
    activeCategory,
}: {
    bookmarks: Bookmark[];
    categories: Category[];
    activeCategory: string | null;
}) {
    const hasPending = items.some((b) => b.status === 'pending');

    useEffect(() => {
        if (!hasPending) {
            return;
        }

        const interval = setInterval(() => {
            router.reload({ only: ['bookmarks'] });
        }, 3000);

        return () => clearInterval(interval);
    }, [hasPending]);

    return (
        <>
            <Head title={`Bookmarks`} />

            <div className={`flex flex-col gap-6 md:flex-row`}>
                <aside className={`md:w-56 md:shrink-0`}>
                    <h2 className={`mb-3 text-sm font-semibold text-muted-foreground`}>Categories</h2>
                    <ul className={`flex flex-col gap-1`}>
                        <li>
                            <Link
                                href={bookmarks.index().url}
                                className={`block rounded px-2 py-1 text-sm hover:bg-muted ${!activeCategory ? 'bg-muted font-medium' : ''}`}
                            >
                                All bookmarks
                            </Link>
                        </li>
                        {categories.map((category) => (
                            <li key={category.id}>
                                <Link
                                    href={bookmarks.index({ query: { category: category.slug } }).url}
                                    className={`flex items-center gap-2 rounded px-2 py-1 text-sm hover:bg-muted ${activeCategory === category.slug ? 'bg-muted font-medium' : ''}`}
                                >
                                    <span
                                        className={`size-3 shrink-0 rounded-full`}
                                        style={{ backgroundColor: category.color ?? '#999' }}
                                    />
                                    <span className={`truncate`}>{category.name}</span>
                                </Link>
                            </li>
                        ))}
                    </ul>
                </aside>

                <div className={`flex-1`}>
                    <div className={`mb-6 flex items-center justify-between`}>
                        <h1 className={`text-xl font-semibold`}>
                            {activeCategory
                                ? categories.find((c) => c.slug === activeCategory)?.name ?? 'Bookmarks'
                                : 'All bookmarks'}
                        </h1>
                        <NewBookmarkDialog categories={categories} />
                    </div>

                    {items.length === 0 ? (
                        <div className={`rounded-lg border border-dashed p-10 text-center text-muted-foreground`}>
                            No bookmarks yet. Click "New Bookmark" to save your first link.
                        </div>
                    ) : (
                        <div className={`grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3`}>
                            {items.map((bookmark) => (
                                <BookmarkCard key={bookmark.id} bookmark={bookmark} />
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}

BookmarksIndex.layout = {
    breadcrumbs: [
        {
            title: 'Bookmarks',
            href: bookmarks.index().url,
        },
    ],
};
