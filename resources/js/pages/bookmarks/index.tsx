import { Head, Link, router } from '@inertiajs/react';
import { SearchIcon, XIcon } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

import { BookmarkCard } from '@/components/bookmark-card';
import { ItemsPagination } from '@/components/items-pagination';
import { NewBookmarkDialog } from '@/components/new-bookmark-dialog';
import { Input } from '@/components/ui/input';
import { useDebounce } from '@/hooks/use-debounce';
import bookmarks from '@/routes/bookmarks';
import type { Bookmark, Category, Paginated } from '@/types';

type Highlights = Record<number, { title?: string; content_text?: string }>;

export default function BookmarksIndex({
    bookmarks: paginatedBookmarks,
    categories,
    activeCategory,
    q,
    highlights,
}: {
    bookmarks: Paginated<Bookmark>;
    categories: Category[];
    activeCategory: string | null;
    q: string | null;
    highlights: Highlights;
}) {
    const items = paginatedBookmarks.data;

    const [query, setQuery] = useState<string>(q ?? '');
    const debouncedQuery = useDebounce(query, 350);
    const isFirstRender = useRef(true);

    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;

            return;
        }

        router.get(
            bookmarks.index({
                query: {
                    q: debouncedQuery || undefined,
                    category: activeCategory ?? undefined,
                },
            }).url,
            undefined,
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only: ['bookmarks', 'highlights', 'q'],
            },
        );
    }, [debouncedQuery, activeCategory]);

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
                    <h2
                        className={`mb-3 text-sm font-semibold text-muted-foreground`}
                    >
                        Categories
                    </h2>
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
                                    href={
                                        bookmarks.index({
                                            query: { category: category.slug },
                                        }).url
                                    }
                                    className={`flex items-center gap-2 rounded px-2 py-1 text-sm hover:bg-muted ${activeCategory === category.slug ? 'bg-muted font-medium' : ''}`}
                                >
                                    <span
                                        className={`size-3 shrink-0 rounded-full`}
                                        style={{
                                            backgroundColor:
                                                category.color ?? '#999',
                                        }}
                                    />
                                    <span className={`truncate`}>
                                        {category.name}
                                    </span>
                                </Link>
                            </li>
                        ))}
                    </ul>
                </aside>

                <div className={`flex-1`}>
                    <div className={`mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between`}>
                        <h1 className={`text-xl font-semibold`}>
                            {activeCategory
                                ? (categories.find(
                                      (c) => c.slug === activeCategory,
                                  )?.name ?? 'Bookmarks')
                                : 'All bookmarks'}
                        </h1>
                        <div className={`flex items-center gap-2`}>
                            <div className={`relative flex-1 sm:w-72 sm:flex-none`}>
                                <SearchIcon
                                    className={`pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground`}
                                />
                                <Input
                                    type={`search`}
                                    value={query}
                                    onChange={(e) => setQuery(e.target.value)}
                                    placeholder={`Search bookmarks...`}
                                    className={`pl-8 ${query ? 'pr-8' : ''}`}
                                />
                                {query && (
                                    <button
                                        type={`button`}
                                        onClick={() => setQuery('')}
                                        aria-label={`Clear search`}
                                        className={`absolute top-1/2 right-2 -translate-y-1/2 text-muted-foreground hover:text-foreground`}
                                    >
                                        <XIcon className={`size-4`} />
                                    </button>
                                )}
                            </div>
                            <NewBookmarkDialog categories={categories} />
                        </div>
                    </div>

                    {items.length === 0 ? (
                        <div
                            className={`rounded-lg border border-dashed p-10 text-center text-muted-foreground`}
                        >
                            {q
                                ? `No bookmarks match "${q}".`
                                : `No bookmarks yet. Click "New Bookmark" to save your first link.`}
                        </div>
                    ) : (
                        <>
                            <div
                                className={`grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3`}
                            >
                                {items.map((bookmark) => (
                                    <BookmarkCard
                                        key={bookmark.id}
                                        bookmark={bookmark}
                                        highlight={highlights?.[bookmark.id]}
                                    />
                                ))}
                            </div>
                            <div className={`mt-4`}>
                                <ItemsPagination
                                    pagination={paginatedBookmarks}
                                />
                            </div>
                        </>
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
            href: '',//bookmarks.index().url,
        },
    ],
};
