# Lavagna ponytail — audit over-engineering

**Report del 2026-08-01** — `ponytail-audit` su tutto l'albero (75 file PHP, 193 TS/TSX, esclusi i generati Wayfinder). Righe e conteggi misurati sul codice a quella data.

Solo over-engineering e complessità: bug di correttezza, sicurezza e performance sono fuori scope e vanno a una review normale. Nessun punto è vincolante: il verdetto va riconfermato quando si tocca il codice.

Tag: `delete` (codice morto) · `stdlib` (lo fa già la libreria standard) · `native` (lo fa già il framework/piattaforma) · `yagni` (astrazione con un solo consumatore) · `shrink` (stessa logica, meno righe).

---

## Blocco A — cancellazioni pure

Zero decisioni di design, nessun impatto sul comportamento. Verificabili con `npm run types:check` + build.

| # | Tag | Punto | Sostituzione | Path | Stima |
|---|-----|-------|--------------|------|-------|
| A1 | `delete` | 5 componenti shadcn con zero import: `collapsible`, `icon`, `placeholder-pattern`, `toggle`, `toggle-group` | nulla | `resources/js/components/ui/` | -181 righe, -5 file |
| A2 | `delete` | Dipendenze mai importate: `radix-ui` (meta-pacchetto, 0 occorrenze di `from 'radix-ui'`, si usano le 13 scoped), `@headlessui/react`. Più `@radix-ui/react-{collapsible,toggle,toggle-group}` che reggono solo i file di A1 | nulla | `package.json` | **-5 deps** |
| A3 | `delete` | 3 layout template morti: `app-header-layout`, `auth-simple-layout`, `auth-split-layout` | nulla | `resources/js/layouts/` | -98 righe, -3 file |
| A4 | `delete` | `TokenAbility::values()` senza chiamanti | nulla | `app/Enums/TokenAbility.php:14-20` | -8 righe |
| A5 | `delete` | Scaffolding Pest mai usato: `expect()->extend('toBeOne')`, `function something()`, i due `ExampleTest` | nulla | `tests/Pest.php`, `tests/{Feature,Unit}/ExampleTest.php` | -25 righe, -2 file |
| A6 | `delete` | `concurrently` e `globals` sono in `dependencies` ma servono solo a `composer dev` e a `eslint.config.js` | spostare in `devDependencies` | `package.json` | bundle di produzione più piccolo |

## Blocco B — semplificazioni locali

Decisione piccola, un file ciascuna.

| # | Tag | Punto | Sostituzione | Path | Stima |
|---|-----|-------|--------------|------|-------|
| B1 | `stdlib` | `BookmarkUrlNormalizer`: una classe + injection + test per togliere il fragment dall'URL | `explode('#', trim($url))[0]` | `app/Services/Bookmarks/BookmarkUrlNormalizer.php` | -14 righe, -1 file |
| B2 | `yagni` | `AiFeatureListenerRegistry`: una classe con un metodo che registra un listener | `Event::listen(...)` inline in `configurePackage()` | `packages/ai/src/AiFeatureListenerRegistry.php` | -18 righe, -1 file |
| B3 | `native` | `Controller.php` base astratto vuoto: Laravel 12+ non lo richiede più (`SummaryController` già non lo estende) | togliere `extends Controller` dagli 8 controller | `app/Http/Controllers/Controller.php` | -14 righe, -1 file |
| B4 | `shrink` | `ProfileValidationRules` espone `nameRules()`/`emailRules()` mai chiamate fuori dal trait | inline in `profileRules()` | `app/Concerns/ProfileValidationRules.php` | -15 righe |
| B5 | `stdlib` | `useClipboard`: 32 righe di hook con stato per un `navigator.clipboard.writeText`, un solo caller | chiamata inline in `two-factor-setup-modal` | `resources/js/hooks/use-clipboard.ts` | -32 righe, -1 file |
| B6 | `shrink` | `useCurrentUrl` esporta 4 type alias (0 usi) + 3 funzioni; `whenCurrentUrl` ha 1 caller | una funzione `isCurrentUrl(href, {startsWith})` | `resources/js/hooks/use-current-url.ts` | -55 righe |

## Blocco C — da discutere

Toccano comportamento, test o superficie pubblica. Non applicabili meccanicamente.

| # | Tag | Punto | Sostituzione | Path | Stima |
|---|-----|-------|--------------|------|-------|
| C1 | `shrink` | `try/catch (\Exception)` ripetuto in 5 action web che flasha `$e->getMessage()` all'utente: duplicazione **e** perdita di interni verso l'utente finale | l'exception handler di Laravel | `app/Http/Controllers/{Category,Bookmark}Controller.php` | -45 righe |
| C2 | `delete` | Action che sono una sola chiamata Eloquent: `DeleteBookmark`, `DeleteCategory`, `DeleteAccount` (`$m->delete()`), `RevokeApiToken`, `IssueApiToken`, `UpdatePassword` | la riga nel controller | `app/Actions/{Bookmarks,Categories,Settings,Auth}` | -82 righe, -6 file. **I test vanno spostati sul controller, non cancellati** |
| C3 | `yagni` | 5 DTO che riavvolgono ciò che la FormRequest già porta: `RevokeTokenData`, `UpdatePasswordData`, `UpdateProfileData`, `UpdateBookmarkProgressData`, `IssueTokenData`. Restano `CreateBookmarkData` e `ListBookmarksFilters` (3+ campi, quest'ultimo attraversa due servizi) | argomenti diretti: `handle($bookmark, $request->integer('progress'))` | `app/Data/**` + i `toData()` in `app/Http/Requests/**` | -68 righe di DTO (-5 file), ~-30 di mapper |
| C4 | `delete` | `Api/V1/StoreBookmarkRequest` identica byte-per-byte a `Bookmarks/StoreBookmarkRequest`; `IndexBookmarksRequest` differisce per due campi | una classe con `$perPage` parametrico | `app/Http/Requests/Api/V1/` | -78 righe, -2 file |
| C5 | `delete` | `welcome.tsx`: landing page dello starter kit mai toccata (fonts.bunny.net, copy Laravel) | redirect a login/dashboard | `resources/js/pages/welcome.tsx` | -400 righe, -1 file |
| C6 | `shrink` | `ui/pagination` espone 7 primitive, `ItemsPagination` ne usa 3 e ci costruisce sopra l'unica UI esistente | fondere in `items-pagination` | `resources/js/components/ui/pagination.tsx` | -90 righe, -1 file |

---

## Totali

| Blocco | Righe | Deps | File |
|---|---|---|---|
| A | ~-312 | -5 | -10 |
| B | ~-148 | — | -4 |
| C | ~-793 | — | -15 |
| **Totale** | **~-1253** | **-5** | **-29** |

## Note

- **C2 e C3 vanno affrontati insieme.** Si sovrappongono su 4 coppie (`RevokeApiToken`/`RevokeTokenData`, `UpdatePassword`/`UpdatePasswordData`, `IssueApiToken`/`IssueTokenData`, `UpdateBookmarkProgress`/`UpdateBookmarkProgressData`): cancellando l'Action sparisce anche il suo DTO. Farli in due passaggi separati significa ricontare tutto al secondo giro.
- I 6 commenti `ponytail:` presenti nel codice (`DashboardStats::weekly`, `AiDashboardStats::weekly`, `AiFeatureServiceProvider` ×2, `SummaryController::store`) sono **semplificazioni deliberate con ceiling dichiarato**, non debito accidentale: restano dove sono. Per l'elenco aggiornato: `/ponytail-debt`.
- Non sono state considerate le directory generate da Wayfinder (`resources/js/{actions,routes,wayfinder}`, gitignored).

## Chiusi

- 2026-08-01 — interfaccia `Action<TInput,TOutput>` rimossa a favore di firme tipizzate sulle 13 Action. Commit `59dbb55`, -114 righe.
