import type { User } from '@/types/auth';

export interface Bookmark {
    id: number;
    user_id: number;
    category_id: number;
    url: string;
    title: string;
    domain: string;
    author: string;
    thumbnail_url: string;
    content_html: string;
    content_text: string;
    reading_progress: number;
    scroll_position: number;
    status: BookmarkStatus;
    user?: User
    category?: Category
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
