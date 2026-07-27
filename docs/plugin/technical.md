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
├── config/ai-summary.php                    parametri di generazione, mergiato dal provider
├── database/migrations/2026_07_26_125358_create_ai_summaries_table.php
├── resources/js/
│   ├── pages/summary.tsx                    pagina Inertia `ai::summary`
│   └── slots.tsx                            componenti da iniettare negli slot del core
├── routes/web.php                           rotte del plugin
└── src/
    ├── AiFeatureServiceProvider.php         punto di ingresso
    ├── AiFeatureListenerRegistry.php        registrazione listener
    ├── Actions/GenerateBookmarkSummary.php  generazione e persistenza del riassunto
    ├── Http/Controllers/SummaryController.php
    ├── Listeners/StartSummaryGeneration.php
    └── Models/AiSummary.php
```

## Dipendenze Composer

Le dipendenze di un plugin si dichiarano nel `composer.json` della root, come l'autoload
PSR-4: `packages/ai` non è un package Composer a sé. Il plugin `ai` aggiunge `laravel/ai`.

**Se i plugin diventano più d'uno**, rivalutare la scelta: un `composer.json` per package con
`repositories: path` renderebbe le dipendenze di ciascun plugin esplicite e rimovibili per
transitività. Il costo è il cambio del meccanismo di attivazione — con quello schema il
provider non può più stare in `bootstrap/providers.php`, perché a package non installato
quella riga punterebbe a una classe inesistente e il boot fallirebbe.

## Configurazione

Un plugin che ha bisogno di configurazione dichiara il proprio file con
`->hasConfigFile('<nome>')`, che spatie risolve in `packages/<plugin>/config/<nome>.php` e
merge nella config dell'applicazione. Il nome non deve collidere con i config esistenti:
qui è `ai-summary` e non `ai`, che appartiene all'AI SDK.

Il file è la fonte autorevole dei parametri di generazione — provider, modello, lunghezza,
timeout e politica di retry del listener — letti sia dall'Action sia dal listener:

```php
// packages/ai/config/ai-summary.php
'provider' => env('AI_SUMMARY_PROVIDER', Lab::OpenAICompatible->value),
'model' => env('AI_SUMMARY_MODEL'),
'sentences' => (int) env('AI_SUMMARY_SENTENCES', 4),
```

`env()` in questo file è legittimo quanto in `config/`: `mergeConfigFrom` non viene eseguito
a config cachata. Larastan lo sa perché `phpstan.neon` dichiara `configDirectories` con il
glob `packages/*/config`.

Il provider e la chiave dell'endpoint restano nelle variabili dell'SDK
(`OPENAI_COMPATIBLE_URL`, `OPENAI_COMPATIBLE_API_KEY`), quindi `config/ai.php` non va
pubblicato: il config di default del package contiene già la voce `openai-compatible`.
Da container Sail l'URL punta a `host.docker.internal`, non a `localhost`.

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

Il listener implementa `ShouldQueue` e resta un adapter puro verso l'Action: nessuna logica
di dominio, solo `tries()` e `backoff()` letti dal config. La coda non è un dettaglio — in
esecuzione sincrona il listener condividerebbe i retry di `ParseArticleContentJob`, e una
chiamata AI fallita farebbe ri-scaricare e ri-parsare l'articolo tre volte.

Un plugin non deve mai far fallire l'operazione del core che ha emesso l'evento. Per questo
`GenerateBookmarkSummary` tratta le condizioni non recuperabili — contenuto assente,
modello non configurato — come uscite pulite (`null` più un warning nel log), non come
eccezioni da ritentare.

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
- `GenerateBookmarkSummaryTest.php` — generazione e persistenza, contenuto assente, modello non configurato, rigenerazione senza duplicati, listener messo in coda dall'evento del core.

Le chiamate all'AI si fingono per agent, non globalmente: `Str::summarize()` usa
`Laravel\Ai\Agents\SummarizeAgent`, quindi `SummarizeAgent::fake([...])` e
`SummarizeAgent::assertPrompted(...)`. In test la coda è sincrona: il listener gira davvero,
ed è il motivo per cui una configurazione mancante deve restare un'uscita pulita.

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