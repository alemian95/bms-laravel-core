import { Head, Link, router, setLayoutProps } from '@inertiajs/react';
import { SearchIcon, XIcon } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

import { BookmarkCard } from '@/components/bookmark-card';
import { ItemsPagination } from '@/components/items-pagination';
import { NewBookmarkDialog } from '@/components/new-bookmark-dialog';
import {
    InputGroup,
    InputGroupAddon,
    InputGroupButton,
    InputGroupInput,
} from '@/components/ui/input-group';
import { useDebounce } from '@/hooks/use-debounce';
import { useTranslation } from '@/hooks/use-translation';
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
    const { t } = useTranslation();

    setLayoutProps({
        breadcrumbs: [{ title: t('Bookmarks'), href: '' }],
    });

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
            <Head title={t('Bookmarks')} />

            <div className={`flex flex-col gap-6 lg:flex-row`}>
                <aside className={`md:w-56 md:shrink-0`}>
                    <h2
                        className={`mb-3 text-sm font-semibold text-muted-foreground`}
                    >
                        {t('Categories')}
                    </h2>
                    <ul className={`flex flex-col gap-1`}>
                        <li>
                            <Link
                                href={bookmarks.index().url}
                                className={`block rounded px-2 py-1 text-sm hover:bg-muted ${!activeCategory ? 'bg-muted font-medium' : ''}`}
                            >
                                {t('All bookmarks')}
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
                    <div
                        className={`mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between`}
                    >
                        <h1 className={`text-xl font-semibold`}>
                            {activeCategory
                                ? (categories.find(
                                      (c) => c.slug === activeCategory,
                                  )?.name ?? t('Bookmarks'))
                                : t('All bookmarks')}
                        </h1>
                        <div className={`flex items-center gap-2`}>
                            <InputGroup
                                className={`flex-1 sm:w-72 sm:flex-none`}
                            >
                                <InputGroupAddon>
                                    <SearchIcon />
                                </InputGroupAddon>
                                <InputGroupInput
                                    type={`search`}
                                    value={query}
                                    onChange={(e) => setQuery(e.target.value)}
                                    placeholder={t('Search bookmarks...')}
                                />
                                {query && (
                                    <InputGroupAddon align={`inline-end`}>
                                        <InputGroupButton
                                            size={`icon-xs`}
                                            onClick={() => setQuery('')}
                                            aria-label={t('Clear search')}
                                        >
                                            <XIcon />
                                        </InputGroupButton>
                                    </InputGroupAddon>
                                )}
                            </InputGroup>
                            <NewBookmarkDialog categories={categories} />
                        </div>
                    </div>

                    {items.length === 0 ? (
                        <div
                            className={`rounded-lg border border-dashed p-10 text-center text-muted-foreground`}
                        >
                            {q
                                ? t('No bookmarks match ":query".', {
                                      query: q,
                                  })
                                : t(
                                      'No bookmarks yet. Click "New Bookmark" to save your first link.',
                                  )}
                        </div>
                    ) : (
                        <>
                            <div
                                className={`grid grid-cols-[repeat(auto-fill,minmax(256px,1fr))] gap-6`}
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
