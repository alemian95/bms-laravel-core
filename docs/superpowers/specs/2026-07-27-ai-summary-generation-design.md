# AI summary generation — design

Data: 2026-07-27 · Branch: `feat/ai-package`

## Obiettivo

Far generare davvero il riassunto di un bookmark al plugin `packages/ai`, installando il
Laravel AI SDK e collegandolo allo scheletro già in piedi. Oggi
`StartSummaryGeneration` esegue solo un `Log::info`: manca il pezzo centrale, cioè
produrre il testo e persisterlo in `ai_summaries`.

## Stato attuale

Il plugin ha già: service provider (`AiFeatureServiceProvider`), registry dei listener,
rotta `ai.summary`, `SummaryController`, model `AiSummary`, migration `ai_summaries`,
pagina Inertia `ai::summary` e slot `bookmark-card-actions`. Il core emette
`ContentParsedEvent` alla fine di `ParseArticleContentJob`.

Non serve toccare il core: l'evento esiste già ed è il punto di aggancio corretto.

## Decisioni

### D1 — La dipendenza `laravel/ai` si dichiara nella root

`packages/ai` non ha un proprio `composer.json`; l'autoload PSR-4 sta nella root. La
dipendenza segue la stessa convenzione.

Alternativa scartata: `composer.json` per package + `repositories: path`. Darebbe
isolamento reale (rimuovere il plugin porterebbe via anche le sue dipendenze per
transitività), ma costa il cambio del meccanismo di attivazione — con path repository il
provider non può più stare in `bootstrap/providers.php`, perché se il package non è
installato quella riga punta a una classe inesistente e il boot fallisce. Con un plugin e
una dipendenza il costo supera il beneficio.

**Nota da lasciare in `docs/plugin/technical.md`:** le dipendenze Composer di un plugin si
dichiarano nella root; se i plugin diventano più d'uno, rivalutare `composer.json` per
package + path repository.

### D2 — Versione `^0.10`

`laravel/ai` è a v0.10.1, pre-1.0: API ancora in movimento. È il rischio principale del
piano ed è accettato consapevolmente.

### D3 — Provider `openai-compatible`

Si usano le API di Unsloth Studio, compatibili OpenAI. L'SDK supporta il provider
`OpenAiCompatible` per text generation, e legge da solo `OPENAI_COMPATIBLE_URL`
(obbligatoria) e `OPENAI_COMPATIBLE_API_KEY` (opzionale, inviata come bearer).

Con Sail l'URL punta all'host dal container, non a `localhost`:
`http://host.docker.internal:1234/v1`.

### D4 — Nessun `vendor:publish` dell'SDK

`config/ai.php` non serve (provider e modello passati espliciti alla chiamata) e le
migration `agent_conversations` / `agent_conversation_messages` riguardano gli agent con
memoria conversazionale, che il summary non usa. L'impronta sul core resta una riga in
`composer.json` e due variabili d'ambiente.

### D5 — Il listener va in coda

`StartSummaryGeneration implements ShouldQueue`.

Oggi il listener girerebbe in-process dentro `ParseArticleContentJob`, che ha `tries = 3`:
una chiamata AI fallita farebbe ri-scaricare e ri-parsare l'articolo tre volte. In coda, il
summary ha i propri retry e un endpoint LLM spento non tocca il parsing del core.

### D6 — Nessun wrapper Contract sull'SDK

`Str::of(...)->summarize()` è API del framework, non un SDK di terzi, ed è già fakeabile
con `Ai::fake()`. Un `SummaryGenerator` con un solo implementatore sarebbe l'astrazione che
il DIP chiede sulla carta e che YAGNI vieta nei fatti ("introdurre solo quando il pattern si
ripete almeno due volte").

Trade-off accettato: se il summary dovrà arrivare da una sorgente diversa dall'SDK, si
modifica l'Action — un file di ~20 righe coperto da test.

### D7 — Una sola fonte per i parametri di generazione

Tutti i parametri della chiamata e del retry vivono in `packages/ai/config/ai-summary.php`,
letto dall'Action e dal listener. Il file non si chiama `ai.php` perché collide con il config
dell'SDK.

Questo esclude di copiare `tries`/`backoff` da `ParseArticleContentJob`: sarebbe duplicare
una decisione operativa del core dentro il plugin.

## Architettura

```
ContentParsedEvent (core)
  └─> StartSummaryGeneration      ShouldQueue, adapter puro evento → Action
        └─> GenerateBookmarkSummary   Action: precondizioni, chiamata, persistenza
              └─> AiSummary            updateOrCreate su bookmark_id
```

Responsabilità:

- **Listener** — traduce l'evento in invocazione dell'Action. Non conosce il dominio
  summary: nessuna guard, nessun accesso a `content_text`. Porta `tries` e `backoff` dal
  config.
- **Action** — unica a conoscere il dominio: verifica le precondizioni, chiama l'SDK,
  persiste. Segue il contratto `App\Actions\Action<Bookmark, AiSummary|null>` già in uso nel
  core.

## File

### Nuovi

| File | Contenuto |
| --- | --- |
| `packages/ai/config/ai-summary.php` | provider, model, sentences, timeout, tries, backoff |
| `packages/ai/src/Actions/GenerateBookmarkSummary.php` | l'operazione di generazione |
| `tests/Feature/Packages/Ai/GenerateBookmarkSummaryTest.php` | test dell'Action e del listener |

### Modificati

| File | Modifica |
| --- | --- |
| `composer.json` (root) | `laravel/ai: ^0.10` |
| `.env.example` | `OPENAI_COMPATIBLE_URL`, `OPENAI_COMPATIBLE_API_KEY`, `AI_SUMMARY_MODEL` |
| `packages/ai/src/AiFeatureServiceProvider.php` | `->hasConfigFile('ai-summary')` |
| `packages/ai/src/Listeners/StartSummaryGeneration.php` | `ShouldQueue`, invoca l'Action, rimuove il `Log::info` |
| `phpstan.neon` | `configDirectories` con glob `packages/*/config` |
| `docs/plugin/technical.md` | sezioni dipendenze, configurazione, test + nota D1 |

Il core applicativo (controller, model, policy, rotte, migration, eventi) non viene toccato.

## Configurazione

```php
// packages/ai/config/ai-summary.php
return [
    'provider' => 'openai-compatible',
    'model' => env('AI_SUMMARY_MODEL', 'local-model'),
    'sentences' => (int) env('AI_SUMMARY_SENTENCES', 4),
    'timeout' => (int) env('AI_SUMMARY_TIMEOUT', 120),
    'tries' => 3,
    'backoff' => [30, 120, 300],
];
```

## Comportamento

| Caso | Esito |
| --- | --- |
| `content_text` vuoto o `null` | l'Action ritorna `null`, nessun record, nessun retry |
| `AI_SUMMARY_MODEL` non configurato | warning nel log e `null`: un plugin non configurato non deve far fallire il parsing del core, e nessun retry può risolvere una misconfigurazione |
| endpoint LLM irraggiungibile o in errore | l'eccezione risale, il listener queued ritenta secondo `tries`/`backoff` |
| summary già presente per quel bookmark | `updateOrCreate`: aggiornato, nessun duplicato |
| pagina summary aperta prima della fine del job | resta lo stato "No summary yet" già presente |

L'ultima riga è una semplificazione deliberata: non si distingue "generazione in corso" da
"mai generato". Distinguerli richiederebbe una colonna `status` su `ai_summaries` e polling
Inertia sulla pagina, che oggi non è richiesto.

## Test

Con `Ai::fake(['fakeText' => '...'])`, nessuna chiamata reale:

1. Action con `content_text` valorizzato → record `ai_summaries` creato con il testo fake,
   `Ai::assertPrompted` sul contenuto del bookmark.
2. Action con `content_text` vuoto → nessun record, nessun prompt inviato.
3. Action su bookmark con summary esistente → record aggiornato, non duplicato.
4. `ContentParsedEvent` dispatchato con `Queue::fake()` → il listener finisce in coda.

I test esistenti del package (`AiPluginSlotTest`, `AiSummaryTest`, `SummaryControllerTest`)
devono restare verdi.

Comando: `./vendor/bin/sail artisan test --compact tests/Feature/Packages`.

## Esito delle verifiche

Chiuse durante l'implementazione, sulla v0.10.1 installata:

- Il config di default dell'SDK contiene già la voce `openai-compatible` che legge
  `OPENAI_COMPATIBLE_URL` e `OPENAI_COMPATIBLE_API_KEY`: nessun `vendor:publish` necessario,
  D4 confermata. Il config del package passa la stringa `Lab::OpenAICompatible->value`
  (grafia dell'enum: `OpenAICompatible`).
- `Stringable::summarize()` accetta `sentences`, `provider`, `model`, `timeout` come da
  documentazione.
- Il fake **non** è globale: `Str::summarize()` usa `Laravel\Ai\Agents\SummarizeAgent`,
  quindi i test usano `SummarizeAgent::fake([...])`, `assertPrompted()` e
  `assertNeverPrompted()`.
- Il provider `openai-compatible` pretende un modello e non ne ha uno di default: senza
  `AI_SUMMARY_MODEL` l'SDK solleva `InvalidArgumentException`. Da qui la guard nell'Action.

## Scostamenti dal design

- **Lo scope `forBookmark()` non è stato creato.** L'Action identifica il record con
  `updateOrCreate(['bookmark_id' => ...])`, che dichiara l'identità della riga e non filtra
  una query: il chiamante reale dello scope sarebbe rimasto uno solo, il `SummaryController`.
  Estrarlo sarebbe stata l'astrazione prematura che YAGNI vieta.
- **Aggiunta la guard sul modello non configurato**, emersa da un test del core che fallisce:
  con la coda sincrona in test, il listener del plugin faceva fallire
  `ParseArticleContentJob`. Correggere il difetto nel plugin era preferibile a modificare il
  test del core.

## Fuori scope

- Stato "in generazione" e polling sulla pagina summary.
- Rigenerazione manuale del summary dall'interfaccia.
- Qualsiasi funzionalità AI oltre il summary (chat, embedding, ricerca semantica).
- Troncamento del contenuto in ingresso per rispettare il context window del modello:
  da affrontare se e quando emergerà con contenuti reali.