# Plugin — documentazione generale

Un **plugin** è una funzionalità opzionale dell'applicazione che vive in una cartella separata sotto `packages/`.
L'applicazione principale (il *core*) funziona anche senza di lui: se il plugin non è attivo, l'utente non vede nulla di quella funzionalità e nessuna sua parte viene eseguita.

Il primo plugin è `ai`, che genera e mostra il riassunto automatico di un bookmark.

## Cosa può fare un plugin

- **Aggiungere pagine e rotte proprie** (es. la pagina del summary).
- **Aggiungere pezzi di interfaccia dentro le pagine del core**, in punti predisposti chiamati *slot* (es. il pulsante "Summary" nella card di un bookmark).
- **Reagire a quello che succede nel core** ascoltando gli eventi che il core emette (es. "il contenuto di un bookmark è stato letto e analizzato").
- **Avere tabelle e dati propri** (es. la tabella dei riassunti).
- **Avere una propria configurazione** (es. quale modello usare per il riassunto) e proprie dipendenze esterne.

## Cosa un plugin non deve fare

- Non modifica il comportamento del core: non riscrive controller, model o pagine esistenti.
- Non è un requisito per far funzionare il core: rimuoverlo non deve rompere nulla.
- Non presume che altri plugin esistano.

## Come si attiva e disattiva

Un plugin è attivo quando il suo *service provider* è registrato in `bootstrap/providers.php`.

- **Attivo** → le sue rotte esistono, i suoi ascoltatori di eventi funzionano, i suoi pulsanti compaiono nell'interfaccia.
- **Disattivato** (riga rimossa) → le rotte spariscono, gli eventi non vengono più ascoltati e i pulsanti non compaiono più, anche se i file del plugin restano sul disco.

Non c'è nessun altro interruttore da toccare: si aggiunge o si toglie una riga.

Attivo non significa però *configurato*: un plugin può aver bisogno di credenziali o di
parametri per lavorare davvero. Quando gli mancano, si ferma e lo scrive nei log, senza far
fallire l'operazione del core che l'ha svegliato.

## Configurazione e dipendenze

Un plugin che ha bisogno di parametri li dichiara in un proprio file dentro
`packages/<nome>/config/`, con valori leggibili dall'ambiente (`.env`). È l'unico posto dove
quei valori sono definiti: nessuna copia sparsa nel codice.

Le librerie esterne di cui un plugin ha bisogno si installano invece nel progetto intero,
perché oggi i plugin non sono pacchetti installabili separatamente. È una scelta presa
finché il plugin è uno solo, e va rivista se ne arrivano altri: il ragionamento completo sta
in [technical.md](technical.md#dipendenze-composer).

## Il plugin `ai`

Genera il riassunto di un bookmark subito dopo che il core ne ha letto e analizzato il
contenuto, e lo mostra in una pagina propria raggiungibile dal pulsante "Summary" nella card
del bookmark.

Il lavoro avviene **in coda**, non mentre l'utente aspetta: il riassunto compare quando è
pronto, ricaricando la pagina. Il testo è prodotto da un modello linguistico contattato su un
endpoint compatibile con le API OpenAI, quindi funziona sia con un servizio esterno sia con
un modello che gira in locale.

Per farlo funzionare servono, in `.env`:

| Variabile | A cosa serve |
| --- | --- |
| `OPENAI_COMPATIBLE_URL` | indirizzo dell'endpoint (da container Docker: `host.docker.internal`, non `localhost`) |
| `OPENAI_COMPATIBLE_API_KEY` | chiave, se l'endpoint la richiede |
| `AI_SUMMARY_MODEL` | nome del modello da usare |

Facoltative: `AI_SUMMARY_SENTENCES` (lunghezza del riassunto, default 4),
`AI_SUMMARY_TIMEOUT`, `AI_SUMMARY_TRIES`, `AI_SUMMARY_PROVIDER`.

Se `AI_SUMMARY_MODEL` manca, il plugin non genera nulla e lascia un avviso nei log: i
bookmark continuano a essere salvati e analizzati normalmente.

## Com'è fatto un plugin

```
packages/<nome>/
├── config/                  parametri configurabili del plugin
├── database/migrations/     tabelle del plugin
├── resources/js/
│   ├── pages/               pagine proprie del plugin
│   └── slots.tsx            pezzi di interfaccia da inserire nelle pagine del core
├── routes/web.php           rotte del plugin
└── src/                     codice PHP (provider, action, controller, model, listener)
```

## Il rapporto con il core

Il core non conosce i plugin per nome: espone dei **punti di aggancio** generici e ogni plugin ci si inserisce da solo.

1. **Slot** — punti dell'interfaccia dove un plugin può inserire un elemento (oggi: la barra di azioni della card di un bookmark).
2. **Pagine dei plugin** — il core sa caricare una pagina che si trova dentro `packages/`, non solo quelle dell'applicazione.
3. **Eventi** — il core annuncia quello che succede; chi vuole ascolta.

Le modifiche fatte al core per rendere possibile tutto questo sono poche e generiche: sono elencate in [technical.md](technical.md#modifiche-al-core).

## Regole di sviluppo

1. **Tutto il codice della funzionalità sta dentro `packages/<nome>/`.** Se serve toccare il core, deve essere per aggiungere un punto di aggancio *generico*, mai un riferimento a un plugin specifico.
2. **Il core non deve sapere che il plugin esiste.** Nessun `if plugin == 'ai'` nel core: cercare il nome di un plugin nei file del core deve dare zero risultati.
3. **Niente modifiche ai model, ai controller e alle pagine del core.** Il plugin legge i dati del core dalle proprie query e aggiunge i propri.
4. **Se il plugin è spento, non deve succedere niente.** Vale per le rotte, per l'interfaccia e per i job in coda.
5. **Se il plugin è acceso ma non configurato, il core non ne risente.** Quello che il plugin non può fare lo scrive nei log e si ferma lì: non fa fallire l'operazione del core che ha emesso l'evento, e non intasa la coda con tentativi destinati a ripetersi uguali.
6. **Ogni plugin ha i suoi test**, in `tests/Feature/Packages/<Nome>/`, incluso un test che verifica che la sua parte di interfaccia sia dichiarata.
7. **La sicurezza resta responsabilità del plugin**: le sue rotte controllano autenticazione e permessi come farebbe il core (una pagina di un plugin non è "meno protetta").

## Da leggere dopo

- [technical.md](technical.md) — come funziona davvero, con nomi di file, codice e la checklist per creare un nuovo plugin.
