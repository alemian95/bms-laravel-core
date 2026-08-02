# Design — App Mobile Flutter (todo 4.4)

**Data:** 2026-08-02
**Stato:** approvato, non implementato
**Riferimento:** `todo.md` § 4.4 — App Mobile (Condivisione Nativa)

## Obiettivo

Salvare un link dal telefono con attrito minimo, tramite il menu Condividi nativo di
iOS e Android, e leggere gli articoli salvati direttamente dall'app.

## Decisioni prese

| Scelta | Decisione | Motivo |
|---|---|---|
| Framework | Flutter | Preferenza esplicita. Un solo runtime, UI prevedibile. Costo accettato: Dart è un terzo linguaggio nel repo. |
| Scope v1 | App completa: share + lista + reader | Richiesto. |
| Reader | WebView con HTML locale | Evita di mantenere un renderer HTML nativo, che si rompe su articoli reali. |
| Share UX | Salvataggio immediato, categoria dopo | L'attrito minimo è il punto di 4.4. |
| Offline | Non supportato | Nessun DB locale né sync. Riduce drasticamente lo scope. |

### Alternative scartate

- **PWA con Web Share Target + Shortcut Apple** — copertura funzionale equivalente in
  giorni anziché settimane, ma scartata a favore di un'app nativa vera.
- **Reader con rendering HTML nativo** — duplica logica già esistente e si rompe su
  articoli complessi.
- **WebView puntata alla rotta web `/bookmarks/{id}/read`** — non praticabile: la rotta
  è Inertia con auth di sessione (un token Sanctum non la autentica) e renderizza
  l'intera shell dell'app dentro la WebView.

## 1. Collocazione e struttura

`apps/mobile/`, accanto a `apps/chrome-extension/`. Codebase Flutter indipendente,
comunica col backend solo via `/api/v1`.

```
apps/mobile/lib/
  api/          client HTTP + modelli (Bookmark, Category)
  auth/         login, token storage, guard
  bookmarks/    lista, filtri, ricerca
  reader/       WebView + tracking scroll
  share/        ricezione intent
```

**Dipendenze**, ridotte all'osso:

- `flutter_riverpod` — stato
- `http` — i token Sanctum non scadono, nessun refresh da gestire: `dio` è superfluo
- `flutter_secure_storage` — token in Keychain/Keystore
- `webview_flutter` — reader
- `receive_sharing_intent` — ricezione intent iOS + Android
- `url_launcher` — apertura del link originale

Escluso `cached_network_image`: `Image.network` ha già cache in memoria, sufficiente
per delle thumbnail.

## 2. Backend — unico lavoro Laravel richiesto

Un solo endpoint nuovo:

```
GET /api/v1/bookmarks/{bookmark}/content
```

- Risorsa `BookmarkContentResource`: `title`, `author`, `domain`, `url`,
  `content_html`, `reading_progress`, `scroll_position`
- Ability `bookmarks:read`, `Gate::authorize('view', $bookmark)`
- Test Pest

Separato da `show` perché `content_html` è pesante e non deve mai entrare nel payload
della lista.

Tutto il resto esiste già e non va toccato: `POST /api/v1/login` (restituisce un token
con abilities `['*']`), `GET /api/v1/bookmarks` (paginato, con ricerca Scout via `q` e
filtro `category_id`), `GET /api/v1/categories`, `POST /api/v1/bookmarks`,
`PATCH /api/v1/bookmarks/{id}/progress`, `DELETE /api/v1/bookmarks/{id}`.

## 3. Auth

Login email/password → `POST /api/v1/login` con `device_name` = nome del dispositivo →
token salvato in `flutter_secure_storage`. Wrapper HTTP che su `401` cancella il token e
rimanda al login. Logout → `POST /api/v1/logout`.

## 4. Share intent

Il salvataggio immediato richiede che la POST avvenga *dentro* l'extension, non aprendo
l'app. Serve quindi **codice nativo**, inevitabile in qualsiasi framework.

**iOS** — target Share Extension in Xcode (~40 righe Swift) che legge l'URL condiviso e
fa la POST. Il token va condiviso tra app ed extension via **Keychain Access Group** in
entitlements di entrambi i target. È il punto tecnicamente più delicato dell'intero
task.

**Android** — `intent-filter` `ACTION_SEND` / `text/plain` su una Activity trasparente
(`Theme.Translucent.NoTitleBar`, ~30 righe Kotlin) che salva e mostra un Toast senza mai
mostrare l'app.

In entrambi i casi: successo → conferma discreta; errore di rete → il link finisce in
una coda locale che l'app ritenta all'apertura, così un salvataggio non si perde mai in
assenza di connessione.

## 5. Lista

`GET /api/v1/bookmarks` con infinite scroll e pull-to-refresh. Card con thumbnail,
titolo, dominio, autore e progress bar da `reading_progress`. Chip di filtro categoria da
`GET /api/v1/categories`, barra di ricerca su `?q=`. Swipe-to-delete → `DELETE`.

## 6. Reader

`WebViewController.loadHtmlString` con un template nostro:

- `<base href>` per risolvere le immagini relative
- CSS mobile: serif, line-height generoso, dark mode via `prefers-color-scheme`
- JS iniettato che calcola la percentuale di scroll e la manda a Flutter via
  `JavascriptChannel`, con debounce 2s → `PATCH /api/v1/bookmarks/{id}/progress`

Il `content_html` è già sanitizzato con HTMLPurifier in scrittura
(`app/Services/ArticleContentParser.php`), quindi la WebView può abilitare JavaScript
senza rischio XSS.

**Resume dello scroll.** `scroll_position` è salvato in pixel dal reader web, e su mobile
la larghezza diversa rende quei pixel privi di senso. Il resume su mobile usa
`reading_progress` (percentuale) riconvertito in pixel sull'altezza del documento nella
WebView. L'app continua comunque a inviare anche `scroll_position` per non rompere il
resume sul web.

## 7. Distribuzione e test

- **iOS**: Apple Developer 99 €/anno. TestFlight (build da rinnovare ogni 90 giorni)
  oppure App Store.
- **Android**: APK diretto, oppure Play Console 25 € una tantum.
- **Test**: `flutter test` sul client API e sulla logica della coda di retry; Pest sul
  nuovo endpoint `content`.

## Dimensionamento

Il backend è mezza giornata. L'app Flutter è la parte grossa, e la share extension iOS
con Keychain condiviso è dove si concentra il rischio di sforare.
