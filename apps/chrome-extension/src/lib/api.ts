import type { ApiError, Bookmark, Category } from './types';
import { getSettings } from './storage';

export class ApiNotConfiguredError extends Error {
    constructor() {
        super('Configure API URL and token in extension options first.');
    }
}

export class ApiRequestError extends Error {
    constructor(
        public readonly status: number,
        public readonly body: ApiError,
    ) {
        super(body.message ?? `Request failed with status ${status}`);
    }
}

async function apiFetch(path: string, init: RequestInit = {}): Promise<Response> {
    const { apiUrl, apiToken } = await getSettings();
    if (!apiUrl || !apiToken) {
        throw new ApiNotConfiguredError();
    }

    const headers = new Headers(init.headers);
    headers.set('Authorization', `Bearer ${apiToken}`);
    headers.set('Accept', 'application/json');
    if (init.body && !headers.has('Content-Type')) {
        headers.set('Content-Type', 'application/json');
    }

    const response = await fetch(`${apiUrl}/api/v1${path}`, {
        ...init,
        headers,
    });

    if (!response.ok) {
        let body: ApiError = {};
        try {
            body = (await response.json()) as ApiError;
        } catch {
            // empty body or non-JSON response
        }
        throw new ApiRequestError(response.status, body);
    }

    return response;
}

export async function listCategories(): Promise<Category[]> {
    const response = await apiFetch('/categories');
    const payload = (await response.json()) as { data: Category[] };
    return payload.data;
}

export async function createBookmark(
    url: string,
    categoryId: number | null = null,
): Promise<Bookmark> {
    const response = await apiFetch('/bookmarks', {
        method: 'POST',
        body: JSON.stringify({
            url,
            category_id: categoryId,
        }),
    });
    const payload = (await response.json()) as { data: Bookmark };
    return payload.data;
}

export async function ping(): Promise<boolean> {
    try {
        await apiFetch('/user');
        return true;
    } catch {
        return false;
    }
}
