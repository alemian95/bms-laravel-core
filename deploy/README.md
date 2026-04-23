# Deploy — bms-core

Configurazione Docker di produzione e guida operativa per rilasciare
**bms-core** su una VPS gestita tramite [Coolify](https://coolify.io).

---

## Panoramica stack di produzione

| Componente      | Tecnologia             | Ruolo                                    |
| --------------- | ---------------------- | ---------------------------------------- |
| Web server + PHP| FrankenPHP (Caddy+PHP) | Serve HTTP, TLS terminato a monte        |
| Queue worker    | `php artisan queue:work` | Processa job (metadata, parsing articoli) |
| Scheduler       | `php artisan schedule:work` | Esegue task pianificati               |
| Database        | PostgreSQL 18          | Storage dati applicativi                 |
| Search          | Meilisearch 1.10       | Full-text search (Scout)                 |
| Reverse proxy   | Traefik (Coolify)      | TLS + routing multi-app                  |

Il build è un'unica immagine multi-stage (`deploy/Dockerfile`) condivisa
fra **app**, **worker** e **scheduler**: cambia solo il comando eseguito
e la variabile `CONTAINER_ROLE`.

---

## Struttura cartella

```
deploy/
├── Dockerfile                      # build multi-stage: vendor → frontend → runtime
├── Dockerfile.dockerignore         # esclusioni dal build context
├── docker-compose.yaml             # 5 servizi: app, worker, scheduler, pgsql, meilisearch
├── .env.production.example         # riferimento variabili d'ambiente
├── docker/
│   ├── Caddyfile                   # config FrankenPHP/Caddy
│   ├── php.ini                     # PHP + OPcache production
│   └── entrypoint.sh               # bootstrap per-ruolo (migrazioni, cache, ecc.)
├── docs/
│   ├── 01-setup-vps.md             # VPS + Docker + Coolify da zero
│   ├── 02-setup-coolify-app.md     # creare la risorsa app in Coolify
│   ├── 03-deployment.md            # primo deploy + aggiornamenti
│   ├── 04-maintenance.md           # backup, log, troubleshooting
│   └── 05-evolving.md              # come evolvere lo stack (Redis, scaling…)
└── README.md                       # questo file
```

---

## Percorso consigliato (nuovo ambiente)

1. Leggi [`docs/01-setup-vps.md`](docs/01-setup-vps.md) e prepara la VPS.
2. Segui [`docs/02-setup-coolify-app.md`](docs/02-setup-coolify-app.md) per
   creare la risorsa Compose in Coolify e configurare dominio + env.
3. Fai il primo rollout con [`docs/03-deployment.md`](docs/03-deployment.md).
4. Imposta backup e monitoring come descritto in
   [`docs/04-maintenance.md`](docs/04-maintenance.md).

## Se già hai Coolify pronto

Vai direttamente a [`docs/02-setup-coolify-app.md`](docs/02-setup-coolify-app.md).

## Se hai cambiato lo stack (es. aggiunto Redis)

Consulta [`docs/05-evolving.md`](docs/05-evolving.md): spiega cosa toccare
in `docker-compose.yaml`, nelle env var e in Coolify, e **come tenere
questa documentazione allineata**.

---

## Quick reference

**Variabili d'ambiente** da impostare in Coolify: vedi
[`.env.production.example`](.env.production.example).

**Endpoint di salute applicazione:** `GET /up` (Laravel default) — usato
anche dall'healthcheck del container `app`.

**Volumi persistenti:**

| Volume             | Contenuto                              |
| ------------------ | -------------------------------------- |
| `pgsql-data`       | Dati PostgreSQL                        |
| `meilisearch-data` | Indici Meilisearch                     |
| `app-storage`      | `storage/app` (upload, file generati)  |
| `app-logs`         | `storage/logs`                         |

**Test locale della config di produzione** (richiede Docker):

```bash
cp deploy/.env.production.example deploy/.env.production
# compilare i valori
docker compose -f deploy/docker-compose.yaml --env-file deploy/.env.production up -d --build
```

Ferma e pulisci:

```bash
docker compose -f deploy/docker-compose.yaml down -v
```
