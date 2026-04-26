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
