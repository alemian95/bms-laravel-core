# Design — PWA con Web Share Target (todo 4.4)

**Data:** 2026-08-02
**Stato:** approvato, non implementato
**Riferimento:** `todo.md` § 4.4 — App Mobile (Condivisione Nativa)
**Alternativa a:** [`mobile-app.md`](mobile-app.md) — si sceglie una sola delle due strade

## Obiettivo

Salvare un link dal telefono con attrito minimo, tramite il menu Condividi nativo di
iOS e Android, senza costruire un'applicazione nativa.

## Principio

Non si costruisce un'app. Si **dichiara** che l'app esistente è installabile e sa
ricevere link condivisi. Dashboard, reader con tracciamento scroll, ricerca Scout e
categorie restano esattamente quelli che già girano in produzione.

## Decisioni prese

| Scelta | Decisione | Motivo |
|---|---|---|
| Ruolo | Alternativa all'app Flutter | Si implementa una sola delle due strade. |
| Offline | Non supportato | Il service worker serve solo all'installabilità. Nessuna cache da invalidare. |
| iOS | Shortcut Apple inclusa | È l'unico modo di avere il share nativo su iOS senza app. |
| Metodo share target | `GET` | La variante POST arriva come `multipart/form-data` e richiede un service worker che la intercetti e re-inoltri. |
| Build tooling | Nessun plugin | Un manifest statico e dieci righe di SW non giustificano `vite-plugin-pwa`. |

## 1. Manifest — `public/manifest.webmanifest`

```json
{
  "name": "Bookmark Management System",
  "short_name": "BMS",
  "start_url": "/dashboard",
  "scope": "/",
  "display": "standalone",
  "icons": [ /* 192, 512, 512-maskable */ ],
  "share_target": {
    "action": "/quick-save",
    "method": "GET",
    "params": { "url": "url", "text": "text", "title": "title" }
  }
}
```

**Il parser deve accettare l'URL da due campi.** Condividendo da Chrome il link arriva in
`url`, ma moltissime app (Twitter/X, Reddit, i client di posta) lo infilano dentro `text`
insieme al titolo. Un'implementazione che legge solo `url` fallisce silenziosamente
proprio sulle app da cui si condivide di più: serve un fallback sul primo `http(s)://`
trovato in `text`.

**Icone.** Servono tre PNG (192, 512, 512 maskable) da generare da `public/favicon.svg`:
oggi in `public/` ci sono solo `favicon.ico`, `favicon.svg` e `apple-touch-icon.png`.

## 2. Service worker — `public/sw.js`

Circa 10 righe, un `fetch` handler pass-through. Chrome elenca ancora un service worker
con fetch handler tra i criteri di installabilità: costa troppo poco per stare a
verificare versione per versione.

Nessuna cache, nessuna strategia offline, niente da invalidare. Registrato con tre righe
in `resources/js/app.tsx`.

## 3. Rotta `/quick-save`

```php
Route::get('quick-save', QuickSaveController::class)
    ->middleware(['auth', 'verified'])->name('quick-save');
```

Il controller estrae l'URL e invoca l'action `CreateBookmark` già esistente
(`app/Actions/Bookmarks/CreateBookmark.php`), ereditando gratis:

- normalizzazione URL via `BookmarkUrlNormalizer`
- controllo duplicati
- la chain `ExtractBookmarkMetadataJob` → `ParseArticleContentJob`

`DuplicateBookmarkException` viene tradotta in un messaggio "già salvato", non in un
errore. Redirect finale alla dashboard con flash toast.

**Auth via cookie di sessione**: nessun token, nessuna scadenza, nessun setup. Se la
sessione è scaduta il middleware `auth` manda al login e l'intended URL riporta l'utente
su `/quick-save` a login fatto — il link non si perde. Questo chiude anche il punto 4.3
rimasto aperto sull'autenticazione automatica.

## 4. iOS — Shortcut Apple

Zero codice nuovo. L'utente crea un token dal preset **Mobile App** già esistente
(`App\Enums\TokenPreset::MobileApp`, abilities `bookmarks:read`, `bookmarks:create`,
`bookmarks:update`, `categories:read`) dalla UI già presente in `/settings/api-tokens`.

La Shortcut fa `POST /api/v1/bookmarks` con header `Authorization: Bearer <token>`. Con
"Mostra nel foglio Condivisione" attivo, compare nel menu Condividi di Safari e di ogni
app. Può opzionalmente leggere `GET /api/v1/categories` e mostrare un picker.

**Limite dichiarato:** la Shortcut si costruisce una volta nell'app Shortcuts e si
distribuisce via link iCloud — non è un artefatto versionabile nel repo. Nel repo va solo
una pagina di istruzioni, da agganciare alla pagina token esistente invece di crearne una
nuova.

## 5. Meta tag iOS — `resources/views/app.blade.php`

`apple-mobile-web-app-capable`, `apple-mobile-web-app-status-bar-style`, più il link al
manifest. Quattro righe. `apple-touch-icon` è già presente.

## 6. Test

Feature test Pest su `/quick-save`:

- URL passato in `url`
- URL annegato dentro `text`
- URL duplicato
- URL non valido
- utente non autenticato

## 7. Limiti, dichiarati

- Su Android l'utente **deve installare** la PWA perché compaia nel menu Condividi. Non
  installata, niente share target.
- Su iOS il share passa dalla Shortcut, che è un setup manuale una tantum per utente.
- Nessuna lettura offline.

## File toccati

1. `public/manifest.webmanifest` — nuovo
2. `public/icon-192.png`, `public/icon-512.png`, `public/icon-maskable-512.png` — nuovi
3. `public/sw.js` — nuovo
4. `resources/views/app.blade.php` — +4 righe
5. `resources/js/app.tsx` — +3 righe
6. `routes/web.php` — +1 riga
7. `app/Http/Controllers/QuickSaveController.php` — nuovo
8. `tests/Feature/QuickSaveTest.php` — nuovo
9. Pagina istruzioni Shortcut iOS — opzionale, dentro la pagina token esistente

Nessuna dipendenza npm o composer nuova.

## Confronto con l'app Flutter

|  | PWA | App Flutter |
|---|---|---|
| File nuovi/toccati | ~9 (3 sono icone) | Nuovo codebase Dart + Swift + Kotlin |
| Backend | Un controller + una rotta | Un endpoint nuovo + resource |
| Linguaggi aggiunti | zero | Dart, Swift, Kotlin |
| Dipendenze aggiunte | zero | 6 pacchetti Flutter |
| Reader | già esistente | da reimplementare in WebView |
| Costi ricorrenti | zero | 99 €/anno Apple Developer |
| Share iOS | Shortcut (setup utente) | nativo |
| Share Android | nativo (se installata) | nativo |
| Offline | no | no (escluso da entrambi) |

La differenza sostanziale è una sola: su iOS la PWA chiede all'utente un setup manuale
una tantum, l'app nativa no. Il resto della colonna Flutter è costo senza differenza
funzionale.
