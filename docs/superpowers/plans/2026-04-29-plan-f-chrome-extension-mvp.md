# Plan F — Browser Extension MVP Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implementare il punto 4.3 del `todo.md`: estensione browser cross-platform (Chrome, Edge, Firefox) che permette di salvare l'URL della tab corrente nel BMS tramite Manifest V3 + Personal Access Token. MVP minimale: popup con select categoria + salvataggio.

**Architecture:** Codebase TypeScript + Vite sotto `apps/chrome-extension/`, indipendente dal backend Laravel a livello di build/deploy ma allineato sui contratti API. Manifest V3 con service worker, popup HTML+TS plain (no React), options page per configurazione one-time di `apiUrl` + `apiToken`. Storage in `chrome.storage.local`. Cross-browser via `webextension-polyfill`.

**Tech Stack:** TypeScript 5, Vite 7, `vite-plugin-web-extension`, `webextension-polyfill`, `@types/chrome`/`@types/webextension-polyfill`. Manifest V3.

---

## Setup di esecuzione

- [ ] **Step S1: Verifica branch**

```bash
git branch --show-current
```

Procedere su un branch dedicato (es. `feat/chrome-extension`) creato da `develop` o `master` corrente. Se sei su `develop`, va bene continuare lì o crearne uno nuovo a discrezione.

---

## File Structure

**Nuovi file:**
- `apps/chrome-extension/package.json`
- `apps/chrome-extension/tsconfig.json`
- `apps/chrome-extension/vite.config.ts`
- `apps/chrome-extension/.gitignore`
- `apps/chrome-extension/README.md`
- `apps/chrome-extension/src/manifest.json`
- `apps/chrome-extension/src/popup.html`
- `apps/chrome-extension/src/options.html`
- `apps/chrome-extension/src/popup.ts` — entry popup
- `apps/chrome-extension/src/options.ts` — entry options
- `apps/chrome-extension/src/background.ts` — service worker
- `apps/chrome-extension/src/lib/storage.ts` — settings get/save
- `apps/chrome-extension/src/lib/api.ts` — API client tipizzato
- `apps/chrome-extension/src/lib/types.ts` — types condivisi (Category, Bookmark, Settings)
- `apps/chrome-extension/src/styles.css` — styling minimale popup + options
- `apps/chrome-extension/src/icons/icon-48.png` — placeholder
- `apps/chrome-extension/src/icons/icon-128.png` — placeholder

**Modificati:**
- `README.md` (root) — sezione "Estensione browser" con istruzioni install dev

---

## Task F1: Scaffold `apps/chrome-extension/`

Genera la struttura base con TypeScript + Vite + plugin web-extension. Niente test runner per ora (l'estensione è stateless, il QA è manuale via "Load unpacked").

**Files:**
- Create: `apps/chrome-extension/package.json`
- Create: `apps/chrome-extension/tsconfig.json`
- Create: `apps/chrome-extension/vite.config.ts`
- Create: `apps/chrome-extension/.gitignore`

- [ ] **Step 1: Crea le directory**

```bash
mkdir -p apps/chrome-extension/src/lib apps/chrome-extension/src/icons
```

- [ ] **Step 2: `package.json`**

```json
{
    "name": "bms-chrome-extension",
    "version": "0.1.0",
    "private": true,
    "type": "module",
    "scripts": {
        "dev": "vite build --watch --mode development",
        "build": "vite build",
        "build:zip": "vite build && cd dist && zip -r ../bms-chrome-extension.zip ./*"
    },
    "devDependencies": {
        "@types/chrome": "^0.0.291",
        "@types/webextension-polyfill": "^0.12.3",
        "typescript": "^5.7.2",
        "vite": "^7.0.0",
        "vite-plugin-web-extension": "^4.4.4"
    },
    "dependencies": {
        "webextension-polyfill": "^0.12.0"
    }
}
```

- [ ] **Step 3: `tsconfig.json`**

```json
{
    "compilerOptions": {
        "target": "ES2022",
        "module": "ESNext",
        "moduleResolution": "Bundler",
        "lib": ["ES2022", "DOM", "DOM.Iterable"],
        "strict": true,
        "noUncheckedIndexedAccess": true,
        "noImplicitOverride": true,
        "esModuleInterop": true,
        "skipLibCheck": true,
        "resolveJsonModule": true,
        "isolatedModules": true,
        "types": ["chrome", "webextension-polyfill"]
    },
    "include": ["src/**/*"]
}
```

- [ ] **Step 4: `vite.config.ts`**

```ts
import { defineConfig } from 'vite';
import webExtension from 'vite-plugin-web-extension';
import { resolve } from 'node:path';

export default defineConfig({
    root: 'src',
    build: {
        outDir: '../dist',
        emptyOutDir: true,
    },
    plugins: [
        webExtension({
            manifest: resolve(__dirname, 'src/manifest.json'),
            additionalInputs: {
                html: ['popup.html', 'options.html'],
            },
        }),
    ],
});
```

- [ ] **Step 5: `.gitignore`**

```
node_modules/
dist/
*.zip
```

- [ ] **Step 6: install dependencies**

```bash
cd apps/chrome-extension && npm install
```

- [ ] **Step 7: Commit**

```bash
git add apps/chrome-extension/package.json apps/chrome-extension/tsconfig.json apps/chrome-extension/vite.config.ts apps/chrome-extension/.gitignore
git commit -m "feat(extension): scaffold chrome-extension with TS + Vite"
```

---

## Task F2: Manifest V3 + types base

**Files:**
- Create: `apps/chrome-extension/src/manifest.json`
- Create: `apps/chrome-extension/src/lib/types.ts`

- [ ] **Step 1: `manifest.json`**

```json
{
    "manifest_version": 3,
    "name": "BMS — Save Bookmark",
    "version": "0.1.0",
    "description": "Save the current tab URL to your Bookmark Management System.",
    "action": {
        "default_popup": "popup.html",
        "default_icon": {
            "48": "icons/icon-48.png",
            "128": "icons/icon-128.png"
        }
    },
    "options_page": "options.html",
    "background": {
        "service_worker": "background.ts",
        "type": "module"
    },
    "permissions": ["activeTab", "storage"],
    "host_permissions": ["<all_urls>"],
    "icons": {
        "48": "icons/icon-48.png",
        "128": "icons/icon-128.png"
    },
    "browser_specific_settings": {
        "gecko": {
            "id": "bms-extension@local"
        }
    }
}
```

- [ ] **Step 2: `src/lib/types.ts`**

```ts
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
```

- [ ] **Step 3: Format & commit**

```bash
git add apps/chrome-extension/src/manifest.json apps/chrome-extension/src/lib/types.ts
git commit -m "feat(extension): add manifest V3 and shared types"
```

---

## Task F3: Storage helper

Wrapper tipizzato sopra `chrome.storage.local`. Centralizza accesso e validazione.

**Files:**
- Create: `apps/chrome-extension/src/lib/storage.ts`

- [ ] **Step 1: Implementa**

```ts
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
```

- [ ] **Step 2: Commit**

```bash
git add apps/chrome-extension/src/lib/storage.ts
git commit -m "feat(extension): add typed chrome.storage.local helper"
```

---

## Task F4: API client

Fetch wrapper con bearer auth, mapping errori a forme typed.

**Files:**
- Create: `apps/chrome-extension/src/lib/api.ts`

- [ ] **Step 1: Implementa**

```ts
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
            // empty body or non-JSON
        }
        throw new ApiRequestError(response.status, body);
    }

    return response;
}

export async function listCategories(): Promise<Category[]> {
    const response = await apiFetch('/categories');
    const data = (await response.json()) as { data: Category[] };
    return data.data;
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
    const data = (await response.json()) as { data: Bookmark };
    return data.data;
}

export async function ping(): Promise<boolean> {
    try {
        await apiFetch('/user');
        return true;
    } catch {
        return false;
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add apps/chrome-extension/src/lib/api.ts
git commit -m "feat(extension): add typed API client with bearer auth"
```

---

## Task F5: Options page

Configurazione one-time: apiUrl + apiToken.

**Files:**
- Create: `apps/chrome-extension/src/options.html`
- Create: `apps/chrome-extension/src/options.ts`
- Create: `apps/chrome-extension/src/styles.css`

- [ ] **Step 1: `styles.css` minimale**

```css
:root {
    --bg: #ffffff;
    --fg: #18181b;
    --muted: #71717a;
    --border: #e4e4e7;
    --primary: #0f766e;
    --primary-hover: #115e59;
    --success: #16a34a;
    --error: #dc2626;
}

@media (prefers-color-scheme: dark) {
    :root {
        --bg: #18181b;
        --fg: #fafafa;
        --muted: #a1a1aa;
        --border: #3f3f46;
    }
}

* { box-sizing: border-box; }
html, body { margin: 0; padding: 0; }
body {
    font-family: system-ui, -apple-system, 'Segoe UI', sans-serif;
    background: var(--bg);
    color: var(--fg);
    font-size: 14px;
}

.container { padding: 16px; }
.popup { width: 320px; }
.options { max-width: 480px; padding: 24px; }

h1 { font-size: 16px; margin: 0 0 12px; }
label { display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px; }
input[type='text'], input[type='password'], input[type='url'], select {
    width: 100%;
    padding: 8px 10px;
    border: 1px solid var(--border);
    border-radius: 4px;
    background: var(--bg);
    color: var(--fg);
    font-size: 13px;
    margin-bottom: 12px;
}
button {
    background: var(--primary);
    color: white;
    border: 0;
    padding: 8px 14px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
}
button:hover:not(:disabled) { background: var(--primary-hover); }
button:disabled { opacity: 0.5; cursor: not-allowed; }
button.secondary { background: transparent; color: var(--fg); border: 1px solid var(--border); }

.url { font-size: 12px; color: var(--muted); word-break: break-all; margin-bottom: 8px; }
.feedback { margin-top: 12px; font-size: 12px; }
.feedback.success { color: var(--success); }
.feedback.error { color: var(--error); }
```

- [ ] **Step 2: `options.html`**

```html
<!doctype html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>BMS Extension Options</title>
        <link rel="stylesheet" href="styles.css" />
    </head>
    <body class="options">
        <div class="container">
            <h1>BMS Extension — Options</h1>

            <form id="settings-form">
                <label for="apiUrl">API base URL</label>
                <input
                    type="url"
                    id="apiUrl"
                    name="apiUrl"
                    placeholder="https://bms.example.com"
                    required
                />

                <label for="apiToken">Personal access token</label>
                <input
                    type="password"
                    id="apiToken"
                    name="apiToken"
                    placeholder="Paste token from /settings/api-tokens"
                    required
                    autocomplete="off"
                />

                <div style="display: flex; gap: 8px;">
                    <button type="submit">Save</button>
                    <button type="button" id="test-button" class="secondary">
                        Test connection
                    </button>
                </div>

                <div id="feedback" class="feedback"></div>
            </form>
        </div>
        <script type="module" src="options.ts"></script>
    </body>
</html>
```

- [ ] **Step 3: `options.ts`**

```ts
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
```

- [ ] **Step 4: Build to verify**

```bash
cd apps/chrome-extension && npm run build
```

Atteso: build OK, `dist/` con `options.html`, `options.js`, `manifest.json`.

- [ ] **Step 5: Commit**

```bash
git add apps/chrome-extension/src/options.html apps/chrome-extension/src/options.ts apps/chrome-extension/src/styles.css
git commit -m "feat(extension): add options page for apiUrl + apiToken"
```

---

## Task F6: Popup

UI popup minimale: URL corrente + select categoria + bottone Save.

**Files:**
- Create: `apps/chrome-extension/src/popup.html`
- Create: `apps/chrome-extension/src/popup.ts`

- [ ] **Step 1: `popup.html`**

```html
<!doctype html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Save Bookmark</title>
        <link rel="stylesheet" href="styles.css" />
    </head>
    <body class="popup">
        <div class="container">
            <h1>Save bookmark</h1>

            <div id="not-configured" hidden>
                <p>
                    Extension not configured. Open
                    <a href="#" id="open-options">options</a> to set API URL and
                    token.
                </p>
            </div>

            <form id="save-form" hidden>
                <label>Current URL</label>
                <div class="url" id="current-url">—</div>

                <label for="category">Category (optional)</label>
                <select id="category" name="category">
                    <option value="">No category</option>
                </select>

                <button type="submit" id="save-button">Save</button>
                <div id="feedback" class="feedback"></div>
            </form>
        </div>
        <script type="module" src="popup.ts"></script>
    </body>
</html>
```

- [ ] **Step 2: `popup.ts`**

```ts
import browser from 'webextension-polyfill';
import { ApiNotConfiguredError, ApiRequestError, createBookmark, listCategories } from './lib/api';
import { isConfigured } from './lib/storage';
import type { Category } from './lib/types';

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
            setFeedback(`Could not load categories: ${(err as Error).message}`, 'error');
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
```

- [ ] **Step 3: Build to verify**

```bash
cd apps/chrome-extension && npm run build
```

- [ ] **Step 4: Commit**

```bash
git add apps/chrome-extension/src/popup.html apps/chrome-extension/src/popup.ts
git commit -m "feat(extension): add popup with category select and save action"
```

---

## Task F7: Background service worker

Minimo: apre la options page al primo install (l'utente non saprebbe come configurarla altrimenti).

**Files:**
- Create: `apps/chrome-extension/src/background.ts`

- [ ] **Step 1: Implementa**

```ts
import browser from 'webextension-polyfill';

browser.runtime.onInstalled.addListener((details) => {
    if (details.reason === 'install') {
        void browser.runtime.openOptionsPage();
    }
});
```

- [ ] **Step 2: Commit**

```bash
git add apps/chrome-extension/src/background.ts
git commit -m "feat(extension): open options page on first install"
```

---

## Task F8: Icone placeholder

Genera 2 icone PNG semplici (in caratteri ASCII per ora, l'utente può sostituirle dopo). Per il MVP usiamo un singolo emoji-style oppure un PNG generato programmaticamente.

**Files:**
- Create: `apps/chrome-extension/src/icons/icon-48.png`
- Create: `apps/chrome-extension/src/icons/icon-128.png`

- [ ] **Step 1: Genera icone**

Le icone devono essere PNG validi. Useremo un piccolo script Node per generarle programmaticamente con un cerchio colorato + lettera "B":

```bash
cd apps/chrome-extension
node -e "
const fs = require('fs');
// Minimal 1x1 transparent PNG placeholder
const png48 = Buffer.from('iVBORw0KGgoAAAANSUhEUgAAADAAAAAwCAYAAABXAvmHAAAAFklEQVR42mNk+M9QzwAEjAxAMAoYAQAOAQEACvSDxgAAAABJRU5ErkJggg==', 'base64');
fs.writeFileSync('src/icons/icon-48.png', png48);
fs.writeFileSync('src/icons/icon-128.png', png48);
"
```

> **Nota:** queste sono icone placeholder 1px trasparenti. Per la pubblicazione su Chrome Web Store servono PNG 48x48 e 128x128 reali — verranno sostituite in fase di publish con asset di design dedicati.

- [ ] **Step 2: Commit**

```bash
git add apps/chrome-extension/src/icons/
git commit -m "feat(extension): add placeholder icons (to be replaced before release)"
```

---

## Task F9: README locale + sezione root README

Documenta come installare l'extension in dev e come buildare.

**Files:**
- Create: `apps/chrome-extension/README.md`
- Modify: `README.md` (root) — sezione "Estensione browser"

- [ ] **Step 1: `apps/chrome-extension/README.md`**

```markdown
# BMS Chrome Extension

MVP extension that saves the current tab URL to your BMS instance via `POST /api/v1/bookmarks`.

## Build

```bash
cd apps/chrome-extension
npm install
npm run build
```

The built artifact is in `dist/`.

## Install in dev

### Chrome / Edge

1. Open `chrome://extensions` (or `edge://extensions`).
2. Toggle **Developer mode** on (top right).
3. Click **Load unpacked** and select the `apps/chrome-extension/dist/` folder.

### Firefox

1. Open `about:debugging#/runtime/this-firefox`.
2. Click **Load Temporary Add-on**.
3. Select `apps/chrome-extension/dist/manifest.json`.

## Configure

1. Open the BMS web app and go to **Settings → API Tokens**.
2. Create a new token with the **Browser Extension** preset (abilities: `bookmarks:create`, `categories:read`).
3. Copy the token (shown only once).
4. Open the extension's **Options** page (`chrome://extensions` → BMS → Options).
5. Paste the API base URL (e.g. `http://localhost`) and the token.
6. Click **Test connection** to verify, then **Save**.

## Use

Click the extension icon on any tab. Choose a category (optional) and click **Save**.

## Build production zip

```bash
npm run build:zip
```

Produces `bms-chrome-extension.zip` ready for Chrome Web Store / Firefox AMO upload.
```

- [ ] **Step 2: Aggiorna root README**

Aggiungi una sezione dopo "Documentazione e test delle API":

```markdown
---

## Estensione browser

Codice in [`apps/chrome-extension/`](apps/chrome-extension). MVP cross-browser (Chrome/Edge/Firefox) basato su Manifest V3 + TypeScript + Vite. Salva l'URL della tab corrente con assegnazione opzionale a una categoria.

### Build rapido

```bash
cd apps/chrome-extension
npm install
npm run build
```

Carica `apps/chrome-extension/dist/` come extension non pacchettizzata. Vedi il [README dell'estensione](apps/chrome-extension/README.md) per istruzioni dettagliate di installazione e configurazione.

### Autenticazione

L'estensione usa un Personal Access Token con preset **Browser Extension** (ability: `bookmarks:create`, `categories:read`). Genera il token da `/settings/api-tokens` e incollalo nelle Options dell'extension.
```

- [ ] **Step 3: Commit**

```bash
git add apps/chrome-extension/README.md README.md
git commit -m "docs(extension): add usage instructions for chrome extension"
```

---

## Task F10: Verifica end-to-end manuale

L'estensione richiede QA manuale (non c'è test runner automatizzato per ora).

- [ ] **Step 1: Build pulita**

```bash
cd apps/chrome-extension && npm run build
```

Atteso: dist/ contiene `manifest.json`, `popup.html`, `options.html`, JS bundle, icone.

- [ ] **Step 2: Carica in Chrome**

Apri `chrome://extensions`, abilita Developer mode, "Load unpacked" → `apps/chrome-extension/dist/`. Atteso: si apre automaticamente la options page.

- [ ] **Step 3: Configura**

- API URL: `http://localhost` (o l'URL del tuo BMS).
- Crea un token via web app: `/settings/api-tokens` → preset "Browser Extension".
- Incolla il token, click "Test connection". Atteso: "Connection OK ✓".
- Click "Save".

- [ ] **Step 4: Test salvataggio**

- Apri qualsiasi articolo (es. `https://example.com/article`).
- Click sull'icona extension. Il popup mostra l'URL e la lista categorie.
- Seleziona una categoria, click "Save". Atteso: "Saved ✓" e popup si chiude.
- Verifica nella web app che il bookmark sia stato creato (status: pending → parsed dopo i job).

- [ ] **Step 5: Test caso 409 (duplicate)**

- Click "Save" sullo stesso URL una seconda volta. Atteso: "Already saved." in errore.

- [ ] **Step 6: Test ability mancante**

- Crea un altro token con preset "Mobile App" (no `bookmarks:create`? No, MobileApp ce l'ha. Crea un test token con ability custom — ma per ora skippa questo test, non c'è UI per scegliere ability arbitrarie).

- [ ] **Step 7: Test Firefox**

Apri `about:debugging` in Firefox, "Load Temporary Add-on", seleziona `dist/manifest.json`. Ripeti i test 3-5.

---

## Note finali Plan F

Al termine del Plan F:
- L'estensione è installabile in dev su Chrome/Edge/Firefox.
- Salva bookmark via API con PAT.
- Options page configurata one-time.
- `apps/chrome-extension/` come prima cellula del monorepo per i client esterni; `apps/mobile/` aggiungibile in futuro con stessa filosofia.
- Il punto 4.3 del `todo.md` può essere marcato `[x]` per i sotto-task: scaffolding, popup, save token, chiamata POST.

**V2 future (fuori scope MVP):**
- Flow OAuth-style invece di PAT manuale: extension apre tab web app, utente clicca "Authorize Extension" → web genera PAT con preset BrowserExtension → redirect URL con token in query string → extension cattura via `chrome.runtime.onConnect` o cookie storage temporaneo. Elimina il copy-paste del token.
- Riconoscimento bookmark già salvato: badge "Saved" sull'icona quando l'URL corrente è già nel BMS.
- Context menu "Save link" sui link dentro le pagine (right-click).
- Notifiche di errore via `browser.notifications.create` invece che solo popup feedback.
