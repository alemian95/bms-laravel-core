import { ping } from './lib/api';
import { getSettings, saveSettings } from './lib/storage';

const form = document.getElementById('settings-form') as HTMLFormElement;
const apiUrlInput = document.getElementById('apiUrl') as HTMLInputElement;
const apiTokenInput = document.getElementById('apiToken') as HTMLInputElement;
const testButton = document.getElementById('test-button') as HTMLButtonElement;
const feedback = document.getElementById('feedback') as HTMLDivElement;

function setFeedback(message: string, kind: 'success' | 'error' | '') {
    feedback.textContent = message;
    feedback.className = kind ? `feedback ${kind}` : 'feedback';
}

async function loadCurrent() {
    const { apiUrl, apiToken } = await getSettings();
    if (apiUrl) apiUrlInput.value = apiUrl;
    if (apiToken) apiTokenInput.value = apiToken;
}

form.addEventListener('submit', async (event) => {
    event.preventDefault();
    setFeedback('', '');
    try {
        await saveSettings({
            apiUrl: apiUrlInput.value.trim(),
            apiToken: apiTokenInput.value.trim(),
        });
        setFeedback('Saved.', 'success');
    } catch (err) {
        setFeedback(`Could not save: ${(err as Error).message}`, 'error');
    }
});

testButton.addEventListener('click', async () => {
    setFeedback('Testing…', '');
    try {
        await saveSettings({
            apiUrl: apiUrlInput.value.trim(),
            apiToken: apiTokenInput.value.trim(),
        });
        const ok = await ping();
        setFeedback(
            ok ? 'Connection OK ✓' : 'Connection failed — check URL and token.',
            ok ? 'success' : 'error',
        );
    } catch (err) {
        setFeedback((err as Error).message, 'error');
    }
});

void loadCurrent();
