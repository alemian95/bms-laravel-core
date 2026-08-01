# Documentazione

## Plugin

Funzionalità opzionali sotto `packages/`, attivabili e disattivabili dal solo `bootstrap/providers.php`.

- [Plugin — documentazione generale](plugin/README.md) — cos'è un plugin, cosa può fare, come si attiva e si configura, regole di sviluppo, [cosa serve al plugin `ai`](plugin/README.md#il-plugin-ai).
- [Plugin — documentazione tecnica](plugin/technical.md) — provider, config e [dipendenze](plugin/technical.md#dipendenze-composer), rotte, eventi, slot frontend, pagine Inertia, [modifiche al core](plugin/technical.md#modifiche-al-core) e checklist per un nuovo plugin.

## Lavagne

Elenchi di lavoro aperti, rivalutati periodicamente (`docs/lavagne/`).

- [ponytail — audit over-engineering](lavagne/ponytail.md) — cosa cancellare, semplificare o sostituire con stdlib/native. Report del 2026-08-01.

## Specifiche

Design validati prima dell'implementazione (`docs/superpowers/specs/`).

- [AI summary generation](superpowers/specs/2026-07-27-ai-summary-generation-design.md) — installazione dell'AI SDK e generazione dei riassunti nel plugin `ai`.

## Piani di implementazione

Piani storici, eseguiti task-by-task dagli agent (`docs/superpowers/plans/`).

- [Plan A — Service Layer Refactor](superpowers/plans/2026-04-29-plan-a-service-layer-refactor.md)
- [Plan B — API Authentication & Personal Access Token Management](superpowers/plans/2026-04-29-plan-b-api-authentication.md)
- [Plan C — Bookmark & Category API Endpoints](superpowers/plans/2026-04-29-plan-c-bookmark-category-api.md)
- [Plan F — Browser Extension MVP](superpowers/plans/2026-04-29-plan-f-chrome-extension-mvp.md)