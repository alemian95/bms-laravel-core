# Design System e Regole UI — Bookmark Management System (BMS)

Questo documento raccoglie le linee guida grafiche, i token visivi, le regole tipografiche e i pattern di interfaccia adottati in **bms-core**, ricavati dall'implementazione reale del frontend (`public/theme.css`, `resources/css/app.css`, `components.json`, `resources/js/**` e `packages/ai/resources/js/**`).

---

## 1. Stack UI e Impostazione Visiva

- **Sistema di componenti:** **shadcn/ui** con variante stilistica `new-york` (`components.json`), basato su primitive accessibili **Radix UI** e **Tailwind CSS v4** (`@import 'tailwindcss'`, `@theme`), con animazioni `tw-animate-css` e plugin `@tailwindcss/typography`.
- **Iconografia:** libreria **Lucide React** (`iconLibrary: "lucide"`).
  - `size-3` (12px): icone inline in badge, link secondari e stati compatti.
  - `size-4` (16px): icone standard di pulsanti, input, navigazione e azioni card.
  - `size-5` (20px): icone delle tile metriche in dashboard.
- **Architettura dei token a due livelli:**
  1. `resources/css/app.css` definisce il fallback neutro, mappa tutte le variabili CSS nella direttiva `@theme` di Tailwind v4 e imposta gli stili base (`border-border`, `cursor-pointer` sui `button`, stile `<mark>`).
  2. `public/theme.css` (caricato dopo `app.css` in `resources/views/app.blade.php`) sovrascrive i token con la palette brand, i font e le ombre definitive.

---

## 2. Palette Colori e Temi (Light / Dark / System)

Tutti i colori semantici sono definiti nello spazio colore **OKLCH** in `public/theme.css`.

### Colori principali e superfici

| Token | Light Mode | Dark Mode | Utilizzo |
| :--- | :--- | :--- | :--- |
| `--background` | `oklch(0.9824 0.0013 286.3757)` | `oklch(0.2303 0.0125 264.2926)` | Sfondo pagina e sidebar |
| `--foreground` | `oklch(0.3211 0 0)` | `oklch(0.9219 0 0)` | Testo primario |
| `--card` / `--popover` | `oklch(1 0 0)` | `oklch(0.3210 0.0078 223.6661)` | Card, modali, popover |
| `--primary` | `oklch(0.6487 0.1538 150.3071)` | `oklch(0.6487 0.1538 150.3071)` | Verde smeraldo: focus ring, progress bar, CTA primarie |
| `--secondary` | `oklch(0.6746 0.1414 261.3380)` | `oklch(0.5880 0.0993 245.7394)` | Blu indaco: azioni secondarie, bolle utente in chat |
| `--accent` | `oklch(0.8269 0.1080 211.9627)` | `oklch(0.6746 0.1414 261.3380)` | Stati hover/attivi ed evidenziazioni |
| `--muted` | `oklch(0.8828 0.0285 98.1033)` | `oklch(0.3867 0 0)` | Sfondi tenui, skeleton, tracce progress bar |
| `--muted-foreground` | `oklch(0.5382 0 0)` | `oklch(0.7155 0 0)` | Metadati, label secondarie, placeholder |
| `--destructive` | `oklch(0.6368 0.2078 25.3313)` | `oklch(0.6368 0.2078 25.3313)` | Stato `failed`, azioni di eliminazione |
| `--border` / `--input` | `oklch(0.8699 0 0)` | `oklch(0.3867 0 0)` | Bordi card, divisori e campi form |

### Palette grafici (`--chart-1` .. `--chart-5`)
Scala coordinata su verde smeraldo, blu e ciano (`oklch(0.6487 0.1538 150.3071)`, `oklch(0.6746 0.1414 261.3380)`, `oklch(0.8269 0.1080 211.9627)`, `oklch(0.5880 0.0993 245.7394)`, `oklch(0.5905 0.1608 148.2409)`).

### Colori dinamici e semantici
- **Categorie utente:** ogni categoria espone un colore HEX configurabile (default `#6366f1` nel picker di `categories/index.tsx`), declinato in tre modi:
  - *Sidebar filtri:* pallino pieno `size-3 rounded-full`.
  - *Card Bookmark (`BookmarkCard`):* badge sopra l'immagine con sfondo `color + 'cc'` (opacità 80%) e testo `#fff`.
  - *Header Reader (`BookmarkHeader`):* pillola morbida con sfondo `color + '22'` (opacità ~13%) e testo nel colore pieno della categoria.
- **Evidenziazione ricerca full-text (`<mark>`):** `bg-yellow-200/60` in light mode e `dark:bg-yellow-500/30` in dark mode, con `rounded px-0.5`.

### Gestione tema (`Light` / `Dark` / `System`)
- **Prevenzione FOUC:** uno script inline nel `<head>` di `resources/views/app.blade.php` legge `localStorage.getItem('appearance')` (o il cookie/prop SSR `$appearance`), applica subito la classe `.dark` su `<html>` e imposta `colorScheme` e `backgroundColor` prima del mount React.
- **Sincronizzazione:** `useAppearance` (`resources/js/hooks/use-appearance.tsx`) persiste la scelta sia in `localStorage` sia in cookie (`appearance`, durata 365 giorni) e ascolta `prefers-color-scheme: dark` in modalità `system`.

---

## 3. Tipografia, Raggi, Ombre e Spaziature

### Famiglie tipografiche
Definite in `public/theme.css`:
- **UI Sans (`--font-sans`):** `Plus Jakarta Sans, sans-serif` (con `Instrument Sans` caricata via Bunny Fonts in `app.blade.php`).
- **Editoriale (`--font-serif`):** `Source Serif 4, serif`.
- **Codice (`--font-mono`):** `JetBrains Mono, monospace`.

### Gerarchia tipografica
- **Titolo articolo (Reader Mode):** `text-3xl leading-tight font-bold` (`BookmarkHeader`).
- **Valori KPI (Dashboard):** `text-3xl font-semibold tracking-tight tabular-nums` (`StatTile`) per mantenere l'allineamento verticale delle cifre.
- **Titoli pagina:** `text-xl font-semibold` (es. catalogo `Bookmarks`).
- **Titoli card:** `font-semibold leading-tight line-clamp-2`.
- **Metadati, domini, autori e azioni compatte:** `text-xs text-muted-foreground`.

### Bordi, raggi e ombre
- **Raggio base (`--radius: 0.5rem` / 8px):**
  - `--radius-sm`: `calc(var(--radius) - 4px)` (4px)
  - `--radius-md`: `calc(var(--radius) - 2px)` (6px)
  - `--radius-lg`: `var(--radius)` (8px — standard per card, alert, empty state e form box)
  - `--radius-xl`: `calc(var(--radius) + 4px)` (12px)
  - Badge, avatar, tag categoria e barre di avanzamento usano `rounded-full`.
- **Elevazione (ombre):** scala `--shadow-2xs` → `--shadow-2xl` basata su `hsl(0 0% 0% / 0.05..0.25)`. Le card usano `shadow-xs` a riposo con transizione a `shadow-md` in hover; i controlli flottanti (pulsante chat AI) usano `shadow-lg`.
- **Spaziatura base (`--spacing: 0.25rem` / 4px):** padding principale contenuto `p-4`, griglie dashboard `gap-4`, griglia bookmark `gap-6`.

---

## 4. Struttura dei Layout e Responsive Design

- **App Shell (`AppSidebarLayout` + `AppSidebar`):**
  - Sidebar laterale collassabile a icone (`collapsible="icon"`) con logo in testa, navigazione principale (`Dashboard`, `Bookmarks`, `Categories`), link footer e menu utente.
  - Header contestuale (`AppSidebarHeader`) con breadcrumb dinamici impostati per pagina tramite `setLayoutProps` (Inertia v3).
  - Area contenuto principale scrollabile (`overflow-auto p-4`).
- **Catalogo Bookmark (`bookmarks/index.tsx`):**
  - Layout a due colonne su desktop (`lg:flex-row`) con filtro categorie laterale (`md:w-56 md:shrink-0`) e griglia auto-adattiva (`grid-cols-[repeat(auto-fill,minmax(256px,1fr))] gap-6`).
  - Barra superiore con titolo categoria attiva, campo ricerca con icona lente/clear (`sm:w-72`) e trigger `NewBookmarkDialog`.
- **Dashboard (`dashboard.tsx`):**
  - Griglia KPI responsiva a 1 / 2 / 4 colonne (`sm:grid-cols-2 xl:grid-cols-4`).
  - Grafico temporale a larghezza piena (`SavesOverTimeChart`), seguito da coppia di grafici comparativi (`lg:grid-cols-2`) e tabella `ContinueReadingTable`.
- **Reader Mode (`bookmarks/read.tsx`):**
  - Colonna singola centrata (`mx-auto max-w-3xl px-4 py-6`) per ottimizzare la lunghezza di riga.
  - Contenuto articolo formattato con `prose prose-lg max-w-none dark:prose-invert`.
- **Impostazioni (`SettingsLayout`):**
  - Sotto-navigazione verticale (`lg:w-48`, separata da `Separator` su mobile) e area form contenuta in `max-w-xl` (`space-y-12`).

---

## 5. Pattern dei Componenti e Stati Visivi

### Card Bookmark (`BookmarkCard`)
- **Anteprima visiva:** contenitore `aspect-[16/9] bg-muted` con `object-cover` e `loading="lazy"`; in assenza di immagine mostra il fallback testuale centrato `"No preview"`.
- **Overlay:** badge categoria in basso a sinistra; badge `destructive` (`Failed`) in alto a destra se il parsing fallisce.
- **Progresso lettura:** barra sottile `h-1 rounded-full bg-muted` con riempimento `bg-primary` visibile solo se `reading_progress > 0`.
- **Footer azioni:** `Read` (primario, con icona libro), `Open` (secondario, link esterno), eventuale estensione tramite `PluginSlot` (`bookmark-card-actions`) e pulsante eliminazione a destra (`hover:text-destructive`).

### Stati di caricamento ed Empty State
- **Skeleton (`animate-pulse bg-muted`):**
  - In `BookmarkCard` (`status === 'pending'`): blocco `aspect-[16/9]` pulsante e due righe (`h-4 w-3/4`, `h-3 w-1/2`).
  - Nel Reader (`status === 'pending'`): 8 righe simulate con larghezze alternate (`85%`, `75%`, `65%`, `55%`).
- **Empty State:** contenitori con bordo tratteggiato (`rounded-lg border border-dashed p-10 text-center text-muted-foreground`), usati in catalogo vuoto, categorie vuote e fallback Reader.

### Data Visualization (`components/dashboard/`)
- **Minimalismo informativo:** i valori singoli usano `StatTile` (numero + label + icona) evitando grafici superflui.
- **Grafici Recharts (`SavesOverTimeChart`, `CountBarChart`):**
  - `AreaChart` con gradiente verticale su `--chart-1` (`stopOpacity` da `0.3` a `0.02`), griglia solo orizzontale (`vertical={false}`, `strokeOpacity={0.4}`), assi puliti (`tickLine={false}`, `axisLine={false}`) e nessuna legenda quando è presente una sola serie.

### Pannello Chat AI (`packages/ai/.../bookmark-chat.tsx`)
- **Trigger:** pulsante flottante fisso in basso a destra (`fixed right-6 bottom-6 z-40 shadow-lg`) nel Reader Mode (`Ask the article`).
- **Drawer:** `Sheet` laterale destro (`sm:max-w-md`) montato on-demand all'apertura.
- **Bolle messaggio:** allineate a destra (`bg-secondary text-secondary-foreground`) per l'utente, a sinistra (`bg-muted text-foreground`) per l'assistente, entrambe con classe `prose text-sm rounded-lg px-3 py-2`; stato di risposta in streaming indicato da tre pallini pulsanti sfalsati (`0ms`, `150ms`, `300ms`).
