# Bookmark Management System (BMS)

BMS è un'applicazione web self-hosted per salvare, organizzare e leggere articoli dal web, ispirata a servizi come Pocket o Instapaper. Permette di archiviare link, estrarne automaticamente metadati e contenuto pulito in background, e offrire un'esperienza di lettura distraction-free con tracciamento dei progressi.

Il progetto è costruito con **Laravel 13**, **Inertia v3 + React 19** e **Tailwind CSS 4**, e utilizza una pipeline di **job asincroni** per l'elaborazione dei contenuti.

---

## Funzionalità

### Gestione Categorie
Organizzazione dei bookmark tramite categorie personalizzate a livello singolo (senza nesting).

- CRUD completo delle categorie dall'interfaccia utente
- Ogni categoria è legata all'utente proprietario e identificata da uno slug
- Possibilità di assegnare (opzionalmente) un bookmark a una categoria in fase di salvataggio

### Salvataggio Bookmark ed Estrazione Metadati
Flusso asincrono per salvare un URL e arricchirlo automaticamente con i dati della pagina.

- Form/modal di inserimento di un nuovo URL, con selezione opzionale della categoria
- Dispatch automatico di `ExtractBookmarkMetadataJob` al salvataggio
- Estrazione di `title`, `domain`, `author` e `thumbnail_url` tramite la libreria `embed/embed`
- Stato del bookmark (`pending`, `parsed`, `failed`) aggiornato in base all'esito del parsing

### Dashboard e Listing
Vista principale "La mia lista" per consultare rapidamente la propria libreria.

- Card dei bookmark con thumbnail, titolo, dominio e autore
- Filtro per categoria tramite query string (`?category=slug`)
- Progress bar visiva sulla card che riflette lo stato di lettura (`reading_progress`)

### Reader Mode (Parsing Articolo)
Visualizzazione pulita dell'articolo, senza distrazioni grafiche del sito originale.

- `ParseArticleContentJob` eseguito dopo l'estrazione dei metadati
- Parsing con `fivefilters/readability.php` (porting PHP di Mozilla Readability)
- Salvataggio di `content_html` (versione pulita per la lettura) e `content_text` (plain-text, utile per indicizzazione futura)
- Rendering sicuro dell'HTML tramite `ezyang/htmlpurifier`
- Rotta dedicata `/bookmarks/{id}/read`

### Tracciamento Lettura e Resume Scroll
La posizione di lettura viene memorizzata lato server, così l'utente può riprendere esattamente dove aveva lasciato, anche da un altro dispositivo.

- Endpoint API `PATCH /api/bookmarks/{id}/progress` per aggiornare `scroll_position` e `reading_progress`
- Script JS nella vista Reader che calcola la percentuale di scroll (0-100%) e invia i dati con debounce ogni 2-3 secondi
- Al caricamento della pagina, lo scroll viene automaticamente riposizionato sull'ultimo offset salvato
- Indicatore visivo di avanzamento nelle card della dashboard

---

## Installazione Locale

### Prerequisiti
- PHP 8.3+
- Composer 2
- Node.js 20+ e pnpm (o npm)
- SQLite (default) oppure MySQL/PostgreSQL

### Setup

```bash
# Clonare il repository
git clone <repo-url> bms-core
cd bms-core

# Dipendenze PHP e frontend
composer install
pnpm install

# Configurazione ambiente
cp .env.example .env
php artisan key:generate

# Database (SQLite di default)
touch database/database.sqlite
php artisan migrate
```

### Avvio ambiente di sviluppo

Comando unico che avvia in parallelo server HTTP, worker della coda, log viewer e Vite:

```bash
composer run dev
```

In alternativa, manualmente in terminali separati:

```bash
php artisan serve
php artisan queue:listen --tries=1 --timeout=0
pnpm run dev
```

L'applicazione sarà disponibile su `http://localhost:8000`.

### Test

```bash
php artisan test --compact
```

### Ricerca

Per il corretto funzionamento della ricerca eseguire i seguenti comandi

```bash
sail artisan scout:index bookmarks
```

e avviare la coda per la sincronizzazione

```bash
sail artisan queue:work
```
