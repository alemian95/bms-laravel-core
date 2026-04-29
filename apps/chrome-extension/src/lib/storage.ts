import browser from 'webextension-polyfill';
import type { Settings } from './types';

const KEYS: (keyof Settings)[] = ['apiUrl', 'apiToken'];

export async function getSettings(): Promise<Partial<Settings>> {
    const stored = (await browser.storage.local.get(KEYS)) as Partial<Settings>;
    return {
        apiUrl: stored.apiUrl?.replace(/\/$/, '') || undefined,
        apiToken: stored.apiToken || undefined,
    };
}

export async function saveSettings(settings: Settings): Promise<void> {
    await browser.storage.local.set({
        apiUrl: settings.apiUrl.replace(/\/$/, ''),
        apiToken: settings.apiToken,
    });
}

export async function isConfigured(): Promise<boolean> {
    const { apiUrl, apiToken } = await getSettings();
    return Boolean(apiUrl && apiToken);
}
