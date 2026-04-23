# 05 — Evolvere lo stack

Lo stack di partenza è volutamente minimale: tutto ciò che può stare
su database (cache, session, queue) ci sta. Quando l'app cresce,
alcuni componenti vanno estratti. Qui trovi le modifiche tipiche
ordinate per probabilità di aver bisogno.

**Regola generale:** ogni volta che aggiungi/rimuovi un servizio o
una env var, aggiorna:

1. `deploy/docker-compose.yaml` — servizi, network, volumi
2. `deploy/.env.production.example` — variabili nuove con commento
3. `deploy/docs/02-setup-coolify-app.md` § "Variabili d'ambiente" —
   elenco delle env var richieste in Coolify
4. Questo file (`05-evolving.md`) — **spunta** la sezione se l'hai
   applicata, così chi ci tornerà dopo sa che il compose è già allineato

Questo doc è sia una **to-do list di evoluzioni future** sia il
**changelog operativo** dello stack.

---

## Aggiungere Redis (cache / session / queue)

Motivazione e timing: vedi analisi in chat / commit di analisi (le
opportunità rilevanti sono cache generale e queue worker).

### Passi

#### 1. Aggiungere il servizio al compose

In `deploy/docker-compose.yaml`, sotto gli altri servizi:

```yaml
  redis:
    image: redis:7-alpine
    restart: unless-stopped
    command: redis-server --appendonly yes --requirepass "${REDIS_PASSWORD}"
    volumes:
      - redis-data:/data
    healthcheck:
      test: ["CMD", "redis-cli", "-a", "${REDIS_PASSWORD}", "ping"]
      interval: 10s
      timeout: 3s
      retries: 5
```

E aggiungi il volume:

```yaml
volumes:
  # …esistenti
  redis-data:
```

#### 2. Env var — aggiornare `x-common-env`

Aggiungi in `x-common-env`:

```yaml
  REDIS_HOST: redis
  REDIS_PORT: 6379
  REDIS_PASSWORD: ${REDIS_PASSWORD}
  REDIS_CLIENT: phpredis
```

E cambia i driver che vuoi spostare su Redis, ad esempio:

```yaml
  CACHE_STORE: redis
  SESSION_DRIVER: redis
  QUEUE_CONNECTION: redis
```

Puoi anche farlo granulare: sposta **solo la queue** prima, valuta
qualche giorno, poi sposta cache e session.

#### 3. Dependencies

I servizi `app`, `worker`, `scheduler` devono aspettare Redis
healthy. Aggiungi in ciascuno:

```yaml
    depends_on:
      redis:
        condition: service_healthy
      # …esistenti
```

#### 4. Estensione PHP `redis`

Nel `deploy/Dockerfile`, stage `runtime`, aggiungi `redis` alla
lista `install-php-extensions`:

```dockerfile
    && install-php-extensions \
        pdo_pgsql \
        redis \
        pcntl \
        …
```

#### 5. Env var Coolify

Aggiungi in Coolify → Environment Variables:

| Variabile         | Valore                                                      |
| ----------------- | ----------------------------------------------------------- |
| `REDIS_PASSWORD`  | `openssl rand -base64 36` — marcala **Is Secret**           |

(e cambia `CACHE_STORE` / `SESSION_DRIVER` / `QUEUE_CONNECTION`
quando sei pronto a tagliare).

#### 6. Migrazione dati (solo se serve)

- **Cache**: si ricostruisce da sola, nessuna migrazione.
- **Session**: al deploy le sessioni attive si invalidano — gli
  utenti dovranno rifare login. Fai il deploy fuori orario, oppure
  cambia il driver solo dopo aver fatto logout di tutti.
- **Queue**: se ci sono job pendenti nel driver `database`:

  ```bash
  # prima del cambio driver
  php artisan queue:work database --stop-when-empty
  ```

  Svuota la queue DB, poi fai deploy col nuovo driver.

#### 7. Rimuovere le migrazioni obsolete

Laravel ha migrazioni per le tabelle `cache`, `sessions`, `jobs`
generate da `make:cache-table`, `session:table`, `queue:table`.
**Lasciale** se il driver potrebbe tornare su database in futuro; in
caso contrario puoi rimuovere le tabelle con una nuova migrazione.

#### 8. Aggiornare la documentazione

- `02-setup-coolify-app.md` — aggiungi `REDIS_PASSWORD` alle env
  obbligatorie.
- `.env.production.example` — aggiungi il blocco Redis.
- Segna ✅ qui sotto quando fatto.

**Stato:** ⬜ non applicato.

---

## Migrare Postgres a risorsa Coolify dedicata

Quando il progetto entra in "vera produzione", conviene togliere
Postgres dal compose e usarlo come **Database Resource** nativa
Coolify — in modo da avere backup schedulati, retention, restore con
un click, e disaccoppiare il lifecycle del DB da quello dell'app.

### Passi

1. **Dump**: dal container Postgres corrente

   ```bash
   docker exec $(docker ps -qf name=pgsql) pg_dump -U bms_core bms_core > /tmp/bms_core.sql
   ```

2. **Crea la risorsa Database** in Coolify UI → **+ New → Database →
   PostgreSQL 18**. Annota il nome interno del servizio (es.
   `postgresql-abc123`) e le credenziali.

3. **Restore**:

   ```bash
   docker cp /tmp/bms_core.sql <container_db_coolify>:/tmp/
   docker exec -it <container_db_coolify> psql -U <user> -d <db> -f /tmp/bms_core.sql
   ```

4. **Collega le risorse**: in Coolify UI sulla app, aggiungi la
   **Database Resource** come linked resource. Coolify espone le
   credenziali come env var disponibili all'app.

5. **Aggiorna env var app**:

   ```
   DB_HOST=postgresql-abc123   # hostname interno della risorsa
   DB_PORT=5432
   DB_DATABASE=<quello scelto in Coolify>
   DB_USERNAME=<quello scelto in Coolify>
   DB_PASSWORD=<quello scelto in Coolify>
   ```

6. **Rimuovi il servizio `pgsql` dal compose** e il volume
   `pgsql-data`. Fai deploy.

7. **Configura backup schedulati** nella risorsa database (UI:
   Backups tab). Destinazione S3/B2 via rclone remote o S3 nativo.

8. **Elimina il volume vecchio** `pgsql-data` solo dopo aver
   verificato che l'app gira sul nuovo DB per almeno qualche giorno.

**Stato:** ⬜ non applicato.

---

## Octane + worker mode (FrankenPHP)

FrankenPHP supporta il "worker mode" Octane: Laravel resta in
memoria fra le request, latency drasticamente più bassa.

Quando conviene: quando la CPU del container app è sopra il 50%
costante sotto carico reale, oppure la TTFB è lenta.

### Passi (alto livello)

1. `composer require laravel/octane`
2. `php artisan octane:install --server=frankenphp`
3. Aggiornare `deploy/docker/Caddyfile` con la directive `frankenphp
   { worker … }` (vedi doc Octane).
4. Cambiare `CMD` nel Dockerfile in `php artisan octane:frankenphp`.
5. **Attenzione**: codice che assume stato "request-scoped" deve
   essere verificato (singleton, bindings, globali).

**Stato:** ⬜ non applicato.

---

## Separare queue worker per priorità

Quando emergono job "veloci" (es. invio email) vs "lenti" (parsing
articoli), conviene avere queue separate:

```yaml
  worker-fast:
    # …same config as worker
    command: php artisan queue:work --queue=default,emails --tries=3 …

  worker-heavy:
    command: php artisan queue:work --queue=articles --tries=3 --timeout=300 …
```

E nei dispatch lato app: `dispatch((new ParseArticleJob)->onQueue('articles'))`.

**Stato:** ⬜ non applicato.

---

## Registry esterno (push immagini da CI)

Quando i build su VPS diventano troppo lenti o impattano la
produzione, sposta il build in CI (GitHub Actions) e pusha su GitHub
Container Registry:

1. GitHub Actions costruisce `ghcr.io/<org>/bms-core-app:<sha>` a
   ogni push.
2. `deploy/docker-compose.yaml`: il servizio `app` usa
   `image: ghcr.io/<org>/bms-core-app:<tag>` e rimuove la sezione
   `build`.
3. Coolify fa solo `docker compose pull && up -d`.

**Stato:** ⬜ non applicato.

---

## Storage off-server per `app-storage`

Se l'app inizia a servire molti upload utente, spostare lo storage
da volume locale a S3:

1. `composer require league/flysystem-aws-s3-v3`
2. Config `filesystems.php` → disk `s3`
3. `.env`: `FILESYSTEM_DISK=s3`, `AWS_*` credentials (in Coolify
   secrets)
4. Migra i file esistenti dal volume locale a S3 con un comando
   artisan ad-hoc.

**Stato:** ⬜ non applicato.

---

## Checklist quando modifichi lo stack

Prima di committare la modifica:

- [ ] `docker-compose.yaml` aggiornato
- [ ] `.env.production.example` con le nuove var e commento
- [ ] `02-setup-coolify-app.md` con le nuove env var obbligatorie
- [ ] Questo file: sezione corrispondente marcata ✅ e aggiunta data
      dell'applicazione
- [ ] Test locale con `docker compose -f deploy/docker-compose.yaml
      --env-file deploy/.env.production up -d --build` prima di
      rilasciare su VPS
