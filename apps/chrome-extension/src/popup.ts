import browser from 'webextension-polyfill';
import {
    ApiNotConfiguredError,
    ApiRequestError,
    createBookmark,
    listCategories,
} from './lib/api';
import { isConfigured } from './lib/storage';

const notConfigured = document.getElementById('not-configured') as HTMLDivElement;
const saveForm = document.getElementById('save-form') as HTMLFormElement;
const currentUrlEl = document.getElementById('current-url') as HTMLDivElement;
const categorySelect = document.getElementById('category') as HTMLSelectElement;
const saveButton = document.getElementById('save-button') as HTMLButtonElement;
const feedback = document.getElementById('feedback') as HTMLDivElement;
const openOptionsLink = document.getElementById('open-options') as HTMLAnchorElement;

function setFeedback(message: string, kind: 'success' | 'error' | '') {
    feedback.textContent = message;
    feedback.className = kind ? `feedback ${kind}` : 'feedback';
}

async function getCurrentTabUrl(): Promise<string | null> {
    const [tab] = await browser.tabs.query({ active: true, currentWindow: true });
    return tab?.url ?? null;
}

async function populateCategories() {
    const categories = await listCategories();
    for (const cat of categories) {
        const option = document.createElement('option');
        option.value = String(cat.id);
        option.textContent = cat.name;
        categorySelect.appendChild(option);
    }
}

async function init() {
    if (!(await isConfigured())) {
        notConfigured.hidden = false;
        openOptionsLink.addEventListener('click', (event) => {
            event.preventDefault();
            void browser.runtime.openOptionsPage();
        });
        return;
    }

    saveForm.hidden = false;

    const url = await getCurrentTabUrl();
    if (!url || !/^https?:\/\//.test(url)) {
        currentUrlEl.textContent = 'No saveable URL in current tab.';
        saveButton.disabled = true;
        return;
    }
    currentUrlEl.textContent = url;
    currentUrlEl.dataset.url = url;

    try {
        await populateCategories();
    } catch (err) {
        if (err instanceof ApiNotConfiguredError) {
            notConfigured.hidden = false;
            saveForm.hidden = true;
        } else {
            setFeedback(
                `Could not load categories: ${(err as Error).message}`,
                'error',
            );
        }
    }
}

saveForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    const url = currentUrlEl.dataset.url;
    if (!url) return;

    saveButton.disabled = true;
    setFeedback('Saving…', '');

    const categoryId = categorySelect.value ? Number(categorySelect.value) : null;

    try {
        await createBookmark(url, categoryId);
        setFeedback('Saved ✓', 'success');
        setTimeout(() => window.close(), 800);
    } catch (err) {
        if (err instanceof ApiRequestError && err.status === 409) {
            setFeedback('Already saved.', 'error');
        } else if (err instanceof ApiNotConfiguredError) {
            setFeedback(err.message, 'error');
        } else {
            setFeedback((err as Error).message, 'error');
        }
        saveButton.disabled = false;
    }
});

void init();
