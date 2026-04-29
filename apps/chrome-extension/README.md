# BMS Chrome Extension

MVP extension che salva l'URL della tab corrente sulla tua istanza BMS via `POST /api/v1/bookmarks`.

Cross-browser (Chrome, Edge, Firefox) basato su Manifest V3, TypeScript e Vite.

## Build

```bash
cd apps/chrome-extension
npm install
npm run build
```

L'output è in `dist/`. Per sviluppo con rebuild automatico ad ogni modifica:

```bash
npm run dev
```

## Installazione in dev

### Chrome / Edge

1. Apri `chrome://extensions` (o `edge://extensions`).
2. Attiva il toggle **Developer mode** in alto a destra.
3. Click **Load unpacked** e seleziona la cartella `apps/chrome-extension/dist/`.

### Firefox

1. Apri `about:debugging#/runtime/this-firefox`.
2. Click **Load Temporary Add-on**.
3. Seleziona `apps/chrome-extension/dist/manifest.json`.

> **Nota Firefox:** le temporary add-on vengono rimosse al riavvio del browser. Per persistenza durante lo sviluppo, usa `web-ext run` (richiede `npm install -g web-ext`).

## Configurazione

1. Apri la web app BMS e vai su **Settings → API Tokens**.
2. Crea un token con il preset **Browser Extension** (abilities: `bookmarks:create`, `categories:read`).
3. Copia il token (mostrato solo una volta).
4. Apri la pagina **Options** dell'estensione: dopo l'install si apre automaticamente; in alternativa `chrome://extensions` → BMS → "Options".
5. Inserisci:
    - **API base URL:** es. `http://localhost` per dev, oppure il dominio della tua istanza.
    - **Personal access token:** il token incollato.
6. Click **Test connection** per verificare. Se OK, click **Save**.

## Uso

Click sull'icona dell'estensione in qualsiasi tab. Seleziona una categoria opzionale e click **Save**. Il bookmark viene creato con stato `pending`; i job di backend ne estrarranno metadati e contenuto.

## Build per distribuzione

```bash
npm run build:zip
```

Produce `bms-chrome-extension.zip` pronto per upload su Chrome Web Store / Firefox AMO.

## Stack

- TypeScript + Vite + `vite-plugin-web-extension`
- `webextension-polyfill` per cross-browser API parity
- Manifest V3 (Chrome MV3 standard, Firefox supporta MV3 da v109+)
- Storage: `chrome.storage.local` (sincronizzato cross-context)

## Limitazioni MVP note

- Le icone sono placeholder (1px PNG trasparente). Da sostituire con asset reali prima della pubblicazione su Chrome Web Store.
- Il flow di autenticazione richiede copia-incolla manuale del PAT. In v2 valutiamo flusso "Authorize from web app" più amichevole.
- Niente badge "Already saved" sull'icona quando l'URL corrente è già presente. Salvare lo stesso URL due volte produce errore "Already saved." in popup (status 409).
