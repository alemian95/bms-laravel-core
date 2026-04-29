export type Settings = {
    apiUrl: string;
    apiToken: string;
};

export type Category = {
    id: number;
    name: string;
    slug: string;
    color: string | null;
    created_at: string | null;
};

export type Bookmark = {
    id: number;
    url: string;
    title: string | null;
    domain: string | null;
    status: 'pending' | 'parsed' | 'failed';
    category_id: number | null;
};

export type ApiError = {
    message?: string;
    errors?: Record<string, string[]>;
};
