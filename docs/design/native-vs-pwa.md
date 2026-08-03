# Valutazione — App Flutter nativa vs PWA (todo 4.4)

**Data:** 2026-08-03
**Stato:** decisione da prendere
**Riferimento:** `todo.md` § 4.4 — App Mobile (Condivisione Nativa)
**Design valutati:** [`mobile-app.md`](mobile-app.md) · [`pwa.md`](pwa.md)

## PWA

**Pro** — zero linguaggi e zero dipendenze nuove, ~9 file toccati, reader e ricerca già
in produzione, auth via cookie di sessione (chiude anche il 4.3 rimasto aperto), nessun
costo ricorrente.

**Contro** — su Android l'utente deve installare la PWA perché compaia nel menu
Condividi; su iOS il share passa da una Shortcut Apple costruita a mano, non
versionabile nel repo; nessuna coda di retry se il salvataggio fallisce.

## App Flutter

**Pro** — share nativo su entrambe le piattaforme senza setup utente, salvataggio dentro
la share extension con coda di retry locale, controllo pieno sulla UX.

**Contro** — nuovo codebase Dart + Swift + Kotlin, 6 dipendenze, reader da
reimplementare in WebView (con resume dello scroll da riconvertire px↔percentuale),
99 €/anno di Apple Developer, e il Keychain Access Group condiviso app↔extension è il
punto dove si concentra il rischio.

## Valutazione per ambito

| Ambito (4.4) | PWA | Flutter |
|---|:--:|:--:|
| **Creare progetto mobile** (4.4.1) — costo di partenza | **5** | **1** |
| **Share target intercept** (4.4.2) — copertura reale iOS + Android | **3** | **5** |
| **Invio silente a `POST /bookmarks`** (4.4.3) | **3** | **5** |
| **Manutenzione e superficie di rischio** | **5** | **2** |
| **Reader / lettura articoli** | **5** | **2** |
| **Affidabilità senza rete al momento del save** | **2** | **4** |

Note sui voti meno ovvi:

- **Share intercept** — su Android sono pari (la PWA solo se installata). Il divario è
  tutto su iOS, dove la Shortcut è un setup manuale una tantum per utente.
- **Invio silente** — paradossalmente su iOS la Shortcut è *più* silente della PWA
  (POST diretta, l'utente resta in Safari), mentre su Android `GET /quick-save` apre la
  finestra standalone e redirige alla dashboard: è un lampo di UI, non un salvataggio
  invisibile.
- **Reader** — il 4.4 non lo richiede, ma il design Flutter lo mette in scope v1, quindi
  pesa nel confronto.

## Raccomandazione

**PWA.** L'unico vantaggio funzionale reale dell'app Flutter è evitare il setup manuale
della Shortcut su iOS e avere la coda di retry offline: due giorni di attrito utente
contro settimane di codice, tre toolchain e un canone annuale.

Se la Shortcut si rivelasse insopportabile all'uso, l'app nativa resta implementabile
sopra la stessa API senza aver buttato nulla di quanto fatto per la PWA.
