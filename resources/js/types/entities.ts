import type { User } from '@/types/auth';

export type Paginated<T> = {
    data: T[];
    current_page: number;
    first_page_url: string;
    from: number;
    last_page: number;
    last_page_url: string | null;
    links: {
        url: string | null;
        label: string;
        page: number | null;
        active: boolean;
    }[];
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number;
    total: number;
};

export interface Bookmark {
    id: number;
    user_id: number;
    category_id: number | null;
    url: string;
    title: string | null;
    domain: string | null;
    author: string | null;
    thumbnail_url: string | null;
    content_html: string | null;
    content_text: string | null;
    reading_progress: number;
    scroll_position: number;
    status: BookmarkStatus;
    created_at?: string;
    updated_at?: string;
    user?: User
    category?: Category | null
}

export type BookmarkStatus = 'pending' | 'parsed' | 'failed';

export interface Category {
    id: number;
    user_id: number;
    name: string;
    slug: string;
    color: string;
    user?: User
    bookmarks?: Bookmark[]
    bookmarks_count?: number
}
