# Bookmark Management System (BMS)

BMS è un'applicazione web self-hosted per salvare, organizzare e leggere articoli dal web, ispirata a servizi come Pocket o Instapaper. Permette di archiviare link, estrarne automaticamente metadati e contenuto pulito in background, e offrire un'esperienza di lettura distraction-free con tracciamento dei progressi e ricerca full-text.

Il progetto è costruito con **Laravel 13**, **Inertia v3 + React 19** e **Tailwind CSS 4**, e utilizza una pipeline di **job asincroni** per l'elaborazione dei contenuti.

---

## Funzionalità

### Gestione Categorie
Organizzazione dei bookmark tramite categorie personalizzate a livello singolo (senza nesting).

- CRUD completo delle categorie dall'interfaccia utente
- Ogni categoria è legata all'utente proprietario, identificata da uno slug e con un colore associato
- Possibilità di assegnare (opzionalmente) un bookmark a una categoria in fase di salvataggio

### Salvataggio Bookmark ed Estrazione Metadati
Flusso asincrono per salvare un URL e arricchirlo automaticamente con i dati della pagina.

- Form/modal di inserimento di un nuovo URL, con selezione opzionale della categoria
- Normalizzazione dell'URL e rilevamento dei duplicati per utente
- Pipeline asincrona via `Bus::chain` di `ExtractBookmarkMetadataJob` → `ParseArticleContentJob`
- Estrazione di `title`, `domain`, `author` e `thumbnail_url` tramite la libreria `embed/embed`
- Stato del bookmark (`pending`, `parsed`, `failed`) aggiornato in base all'esito del parsing

### Dashboard e Listing
Vista principale "La mia lista" per consultare rapidamente la propria libreria.

- Card dei bookmark con thumbnail, titolo, dominio e autore
- Filtro per categoria tramite query string (`?category=slug`)
- Paginazione lato server
- Progress bar visiva sulla card che riflette lo stato di lettura (`reading_progress`)

### Reader Mode (Parsing Articolo)
Visualizzazione pulita dell'articolo, senza distrazioni grafiche del sito originale.

- `ParseArticleContentJob` eseguito in chain dopo l'estrazione dei metadati
- Parsing con `fivefilters/readability.php` (porting PHP di Mozilla Readability)
- Salvataggio di `content_html` (versione pulita per la lettura) e `content_text` (plain-text, utile per indicizzazione e ricerca)
- Rendering sicuro dell'HTML tramite `ezyang/htmlpurifier`
- Rotta dedicata `/bookmarks/{bookmark}/read`

### Tracciamento Lettura e Resume Scroll
La posizione di lettura viene memorizzata lato server, così l'utente può riprendere esattamente dove aveva lasciato, anche da un altro dispositivo.

- Endpoint `PATCH /bookmarks/{bookmark}/update-progress` per aggiornare `scroll_position` e `reading_progress`
- Hook React (`useHttp` di Inertia v3) nella vista Reader che calcola la percentuale di scroll (0-100%) e invia i dati con debounce di ~1 secondo
- Al caricamento della pagina, lo scroll viene automaticamente riposizionato sull'ultimo offset salvato
- `reading_progress` viene aggiornato come massimo storico (non regredisce se l'utente torna indietro)
- Indicatore visivo di avanzamento nelle card della dashboard

### Ricerca Full-Text
Ricerca rapida sull'intera libreria personale tramite Laravel Scout + Meilisearch.

- Modello `Bookmark` indicizzato con il trait `Searchable`
- Indicizzazione di `title`, `author`, `domain`, `content_text` e nome della categoria
- Barra di ricerca nella dashboard con debounce lato client (350 ms)
- Filtri Scout per `user_id` e `category_id` per garantire isolamento per utente
- Highlighting dei termini cercati nei risultati
- Sincronizzazione dell'indice tramite coda (`SCOUT_QUEUE=true`)

---

## Stack Tecnico

- **Backend:** PHP 8.4, Laravel 13, Inertia Laravel v3
- **Frontend:** React 19, Inertia React v3, Tailwind CSS 4, Wayfinder (route typing)
- **Database:** PostgreSQL (default tramite Docker, SQLite supportato per sviluppo rapido)
- **Search:** Meilisearch via Laravel Scout 11
- **Auth:** Laravel Fortify (web), Laravel Sanctum installato e predisposto per future API esterne
- **Queue:** Database driver di default
- **Testing:** Pest 4

---

## Installazione Locale

L'ambiente di sviluppo locale si basa su **Laravel Sail**: il file `compose.yaml` definisce il container applicativo (PHP 8.4) insieme ai servizi PostgreSQL, Meilisearch e Mailpit.

### Prerequisiti
- Docker / Docker Compose
- PHP 8.4+ e Composer 2 (solo per il primo `composer install` lato host; in alternativa si può eseguire via container)
- Node.js 20+ e pnpm (o npm)

### Setup

```bash
git clone <repo-url> bms-core
cd bms-core

cp .env.example .env

# Dipendenze (lato host)
composer install
pnpm install

# Avvio dei container Sail (app, pgsql, meilisearch, mailpit)
./vendor/bin/sail up -d

# Chiave applicativa e migrazioni
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
```

> Suggerimento: aggiungere un alias `alias sail='./vendor/bin/sail'` per accorciare i comandi.

### Avvio ambiente di sviluppo

Una volta che i container sono attivi, avviare in parallelo queue worker, log viewer e Vite con:

```bash
sail composer run dev
```

Lo script lancia `artisan serve`, `queue:listen`, `pail` e `npm run dev` in un'unica sessione.

L'applicazione sarà disponibile su `http://localhost` (porta `APP_PORT`, default `80`).
Mailpit è raggiungibile su `http://localhost:8025` e Meilisearch su `http://localhost:7700`.

### Indicizzazione iniziale della ricerca

Per popolare l'indice Meilisearch con i bookmark esistenti:

```bash
sail artisan scout:import "App\Models\Bookmark"
```

La coda deve essere attiva per la sincronizzazione automatica delle modifiche (già inclusa in `composer run dev`); in alternativa:

```bash
sail artisan queue:work
```

### Test

```bash
sail artisan test --compact
```

---

## Documentazione e test delle API (Swagger UI)

Le API REST esposte sotto `/api/v1/...` sono documentate automaticamente tramite **[Scramble](https://scramble.dedoc.co/)**, che ispeziona Form Request, API Resources e route signatures per generare uno spec OpenAPI 3.1 e una Swagger UI navigabile.

### Accesso

Con i container Sail attivi, apri:

- **Swagger UI:** [http://localhost/docs/api](http://localhost/docs/api)
- **OpenAPI JSON:** [http://localhost/docs/api.json](http://localhost/docs/api.json)

L'accesso è regolato dal Gate `viewApiDocs` definito in `AppServiceProvider`. Per default è consentito in tutti gli ambienti **eccetto produzione**: quando `APP_ENV=production` la UI risponde `403`. Se vuoi cambiare la policy (es. richiedere login admin), modifica la definizione del Gate.

### Workflow per testare un endpoint autenticato

1. Vai su `http://localhost/settings/api-tokens` (devi essere loggato in web).
2. Crea un nuovo token scegliendo un preset (`Browser Extension`, `Mobile App`, `Full Access`). Il token in chiaro è mostrato **una sola volta** subito dopo la creazione — copialo.
3. Apri la Swagger UI su `/docs/api` e clicca il pulsante **"Authorize"** in alto a destra.
4. Incolla il token nel campo `bearerAuth` e conferma.
5. Espandi qualsiasi endpoint, clicca **"Try it out"**, compila i parametri e premi **"Execute"** per inviare la richiesta reale: la UI userà il Bearer token per tutte le rotte sotto `auth:sanctum`.

L'endpoint `POST /api/v1/login` accetta `{ email, password, device_name }` e restituisce un token di login (separato dai PAT generati dalla dashboard) — utile per testare il flusso di autenticazione mobile.

### Aggiornamento dello spec

Lo spec viene rigenerato automaticamente a ogni richiesta (in dev). Quando aggiungi nuove rotte o nuovi Form Request / Resource, basta ricaricare la pagina `/docs/api`.
