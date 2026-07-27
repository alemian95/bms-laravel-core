# Plugin — documentazione tecnica

Riferimento di implementazione: il package `ai` (`packages/ai`). Per il quadro generale vedi [README.md](README.md).

## Stack e convenzioni

- Un plugin è un package Laravel locale basato su `spatie/laravel-package-tools`, autoloadato dal `composer.json` della root:
  ```json
  "autoload": { "psr-4": { "BmsCore\\Packages\\Ai\\": "packages/ai/src/" } }
  ```
- Il provider è registrato in `bootstrap/providers.php`. **Questa riga è l'unico interruttore del plugin**: registra rotte, migration, listener e la flag frontend.
- Le classi seguono le convenzioni del core: type hint e return type espliciti, controller sottili, `Gate::authorize()` per i permessi.

```
packages/ai/
├── database/migrations/2026_07_26_125358_create_ai_summaries_table.php
├── resources/js/
│   ├── pages/summary.tsx                    pagina Inertia `ai::summary`
│   └── slots.tsx                            componenti da iniettare negli slot del core
├── routes/web.php                           rotte del plugin
└── src/
    ├── AiFeatureServiceProvider.php         punto di ingresso
    ├── AiFeatureListenerRegistry.php        registrazione listener
    ├── Http/Controllers/SummaryController.php
    ├── Listeners/StartSummaryGeneration.php
    └── Models/AiSummary.php
```

## Il service provider

```php
public function configurePackage(Package $package): void
{
    $package->name('ai')
        ->discoversMigrations()
        ->runsMigrations()
        ->hasRoute('web');            // carica packages/ai/routes/web.php

    app(AiFeatureListenerRegistry::class)->registerListeners();
}

public function packageBooted(): void
{
    Inertia::share('plugins.ai', true);   // flag letta dal frontend
}
```

Note:

- `hasRoute('web')` risolve `basePath('/../routes/') . 'web.php'`, cioè `packages/ai/routes/web.php`. `loadRoutesFrom()` **non** applica il middleware group `web`: va dichiarato nel file di rotte.
- `Inertia::share()` usa `Arr::set()`, quindi la dot notation `plugins.ai` produce la prop condivisa `plugins: { ai: true }`. Ogni plugin scrive la propria chiave: nessun merge, nessun conflitto.

## Eventi

Il core emette eventi di dominio; il plugin li ascolta senza toccare chi li emette.

```php
// packages/ai/src/AiFeatureListenerRegistry.php
Event::listen(ContentParsedEvent::class, StartSummaryGeneration::class);
```

`registerListeners()` è chiamato dal provider: se il provider non è registrato, nessun listener è agganciato e l'evento del core passa a vuoto. La registrazione sta in una classe dedicata (non nel provider) così il set di listener resta leggibile e testabile.

## Rotte

```php
// packages/ai/routes/web.php
Route::middleware(['web', 'auth', 'verified'])->group(function () {
    Route::get('bookmarks/{bookmark}/summary', SummaryController::class)->name('ai.summary');
});
```

- Prefisso del nome per plugin (`ai.*`) per evitare collisioni con le rotte del core.
- Wayfinder genera automaticamente `resources/js/routes/ai/index.ts` (cartella generata e in `.gitignore`), quindi il frontend del plugin usa `ai.summary(bookmark.id).url` invece di URL scritti a mano.

## Accesso ai dati del core

Il plugin **non** aggiunge relazioni ai model del core. Interroga per chiave esterna:

```php
Gate::authorize('view', $bookmark);   // policy del core

return Inertia::render('ai::summary', [
    'bookmark' => $bookmark->only(['id', 'title', 'url', 'domain']),
    'summary' => AiSummary::where('bookmark_id', $bookmark->id)->value('summary'),
]);
```

## Slot frontend

Meccanismo per inserire un componente di un plugin dentro una pagina del core.

**Lato core** — `resources/js/components/plugin-slot.tsx`:

```tsx
const modules = import.meta.glob<{ default: SlotMap }>(
    '/packages/*/resources/js/slots.tsx',
    { eager: true },
);
```

Il glob raccoglie i file `slots.tsx` di tutti i package presenti; dal path si ricava il nome del plugin (`path.split('/')[2]`). Al render, `PluginSlot` mostra il componente registrato per quel nome di slot **solo se** `usePage().props.plugins[<plugin>]` è vero: il glob è filesystem, la flag arriva dal provider, quindi provider spento = pulsante assente anche con i file sul disco.

Il punto di aggancio nella pagina del core è una riga:

```tsx
<PluginSlot name={`bookmark-card-actions`} bookmark={bookmark} />
```

**Lato plugin** — `packages/ai/resources/js/slots.tsx` esporta una mappa `slot → componente`:

```tsx
export default {
    'bookmark-card-actions': SummaryButton,
};
```

Le props passate a `PluginSlot` (oltre a `name`) arrivano al componente del plugin. Il contratto è per convenzione (`Record<string, unknown>`), non tipizzato per slot.

Slot esistenti:

| Nome | Posizione | Props |
| --- | --- | --- |
| `bookmark-card-actions` | barra azioni della card bookmark | `bookmark: Bookmark` |

## Pagine Inertia dei plugin

Le pagine dei plugin usano il nome `<plugin>::<pagina>` (es. `ai::summary`) e vivono in `packages/<plugin>/resources/js/pages/`.

**Risoluzione client** — `resources/js/app.tsx` definisce un `resolve` esplicito con due glob (core + plugin):

```tsx
const corePages = import.meta.glob('./pages/**/*.tsx');
const pluginPages = import.meta.glob('/packages/*/resources/js/pages/**/*.tsx');
```

`ai::summary` → `/packages/ai/resources/js/pages/summary.tsx`. Il `layout` del core continua a funzionare sui nomi: senza prefisso `auth/` o `settings/` la pagina riceve `AppLayout`.

**Preload lato server** — `resources/views/app.blade.php` traduce lo stesso nome nell'entry Vite da precaricare:

```php
$pageEntry = str_contains($page['component'], '::')
    ? 'packages/'.str_replace('::', '/resources/js/pages/', $page['component']).'.tsx'
    : "resources/js/pages/{$page['component']}.tsx";
```

Le pagine dei plugin finiscono nel manifest Vite perché sono nel grafo dei moduli tramite il glob (chiave `packages/ai/resources/js/pages/summary.tsx`).

**Conseguenze da conoscere:**

- Definendo un `resolve` esplicito, il plugin `@inertiajs/vite` non inietta il suo resolver e non fa il *warmup* dei file pagina in dev: è solo una perdita di prestazioni al primo caricamento in dev, non di funzionalità.
- Nei test, `AssertableInertia::component()` cerca il file sotto `resources/js`: per una pagina di plugin va passato `shouldExist: false` → `->component('ai::summary', false)`.

## Test

In `tests/Feature/Packages/<Nome>/`, con Pest:

- `AiPluginSlotTest.php` — verifica che la prop `plugins.ai` sia condivisa (se salta, il pulsante sparisce dall'interfaccia).
- `SummaryControllerTest.php` — pagina con summary, pagina senza summary, `403` sul bookmark di un altro utente.

```bash
./vendor/bin/sail artisan test --compact tests/Feature/Packages
```

## Modifiche al core

Tutto quello che è stato aggiunto al core per il supporto plugin. Nessuna di queste modifiche nomina il package `ai`.

| File | Tipo | Cosa fa |
| --- | --- | --- |
| `resources/js/components/plugin-slot.tsx` | nuovo (~30 righe) | Componente `PluginSlot`: glob dei `slots.tsx` dei package e render condizionato alla prop `plugins.<nome>`. |
| `resources/js/components/bookmark-card.tsx` | +5 righe | Aggiunge lo slot `bookmark-card-actions` nella barra azioni. |
| `resources/js/app.tsx` | +20 righe | `resolve` esplicito di `createInertiaApp`: risolve sia `pagina` (core) sia `plugin::pagina` (package). |
| `resources/views/app.blade.php` | +8 righe | Calcola l'entry Vite da precaricare anche per le pagine `plugin::pagina`. |
| `tsconfig.json` | +1 riga | `include` di `packages/*/resources/js/**/*.tsx` per il type check. |

Non sono stati toccati: controller, model, policy, rotte, migration ed eventi del core.

## Checklist per un nuovo plugin

1. Creare `packages/<nome>/src/` e aggiungere lo PSR-4 in `composer.json`, poi `composer dump-autoload`.
2. Scrivere `<Nome>ServiceProvider extends PackageServiceProvider`: `->name('<nome>')`, migration, `->hasRoute('web')` se servono rotte, e `Inertia::share('plugins.<nome>', true)` in `packageBooted()` se serve interfaccia.
3. Registrare il provider in `bootstrap/providers.php`.
4. Rotte in `packages/<nome>/routes/web.php` con middleware `['web', 'auth', ...]` e nomi prefissati `<nome>.*`.
5. Pagine in `packages/<nome>/resources/js/pages/`, renderizzate con `Inertia::render('<nome>::<pagina>')`.
6. Elementi di interfaccia in `packages/<nome>/resources/js/slots.tsx`, con chiave uguale a uno slot esistente.
7. Listener degli eventi del core in una `<Nome>ListenerRegistry` chiamata dal provider.
8. Test in `tests/Feature/Packages/<Nome>/`.
9. Verificare: `./vendor/bin/sail artisan test --compact`, `./vendor/bin/sail bin pint --dirty`, `./vendor/bin/sail npm run types:check`, `./vendor/bin/sail npm run build`.
10. Prova di spegnimento: rimuovere il provider da `bootstrap/providers.php` e verificare che il core resti verde e l'interfaccia pulita.

## Serve un nuovo slot?

Aggiungere `<PluginSlot name={...} ... />` nel punto voluto di una pagina del core e documentarlo nella tabella degli slot. È l'unico tipo di modifica al core ammessa per un plugin, e deve restare generica (nessun riferimento a un plugin specifico).