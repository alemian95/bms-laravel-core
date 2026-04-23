# 02 — Configurazione dell'applicazione in Coolify

Presupposti:

- Coolify è installato e raggiungibile via browser (dashboard HTTPS o
  `http://IP_VPS:8000`).
- Il repository bms-core è su GitHub (o altra Git forge
  supportata — GitLab, Bitbucket, Gitea).

Obiettivo: creare in Coolify una risorsa basata su
`deploy/docker-compose.yaml`, collegarla al repo, esporre il dominio
pubblico e configurare le variabili d'ambiente.

---

## 1. Project e Environment

In Coolify le risorse vivono dentro **Project → Environment**. Il
setup di default (`Project: default`, `Environment: production`) va
bene per cominciare.

Se vuoi separare ambienti:

- **Projects → New Project** → `bms-core`
- dentro il progetto, Environment `production`

---

## 2. Collegare il repository Git

1. Nella sidebar: **Sources → New Source**.
2. Seleziona il provider (es. **GitHub App**) e autorizza l'accesso
   al repo bms-core. In alternativa puoi usare un deploy key SSH su un
   repo privato generico.
3. Verifica che il repo compaia nella lista delle source.

---

## 3. Creare la risorsa "Docker Compose"

1. Dentro il tuo progetto/ambiente: **+ New → Resource → Public
   Repository** (se pubblico) o **Private Repository (with GitHub App)**.
2. Incolla l'URL del repo e seleziona il branch `master`.
3. **Build Pack**: scegli **Docker Compose**.
4. **Docker Compose Location**: `deploy/docker-compose.yaml`.
5. **Base Directory**: `/` (la root del repo — il compose usa
   `context: ..` per avere l'intero progetto a disposizione del
   Dockerfile).
6. Conferma la creazione.

Coolify fa il parse del compose e mostra i 5 servizi: `app`, `worker`,
`scheduler`, `pgsql`, `meilisearch`.

---

## 4. Dominio pubblico

Solo `app` deve essere raggiungibile dall'esterno.

1. Apri il servizio **app** nella UI Coolify.
2. Tab **Domains**: inserisci il dominio (es. `https://bms.tuodominio.it`).
3. Assicurati che il DNS del dominio punti già all'IP della VPS
   (record A).
4. Salva. Coolify crea la rotta Traefik e richiede il certificato
   TLS a Let's Encrypt automaticamente.

Gli altri servizi (worker, scheduler, pgsql, meilisearch) non hanno
dominio: rimangono accessibili solo all'interno della rete Docker
interna del progetto.

---

## 5. Variabili d'ambiente

Tab **Environment Variables** a livello di risorsa (non del singolo
servizio): le variabili vengono iniettate in tutti i servizi che le
referenziano nel compose.

Prendi come riferimento [`../.env.production.example`](../.env.production.example)
e inserisci in Coolify almeno queste:

### Obbligatorie

| Variabile        | Come ottenerla / valore                                       |
| ---------------- | ------------------------------------------------------------- |
| `APP_KEY`        | `php artisan key:generate --show` (in locale) → copia         |
| `APP_URL`        | lo stesso dominio impostato al punto 4, con `https://`        |
| `DB_DATABASE`    | `bms_core`                                                    |
| `DB_USERNAME`    | `bms_core`                                                    |
| `DB_PASSWORD`    | genera con `openssl rand -base64 36`                          |
| `MEILISEARCH_KEY`| genera con `openssl rand -hex 32`                             |

### Consigliate

| Variabile        | Default     | Quando cambiare                                  |
| ---------------- | ----------- | ------------------------------------------------ |
| `APP_NAME`       | `bms-core`  | se vuoi personalizzare                           |
| `APP_LOCALE`     | `it`        | —                                                |
| `LOG_LEVEL`      | `info`      | `debug` in temporaneo per troubleshooting        |
| `CACHE_STORE`    | `database`  | vedi [05-evolving.md](05-evolving.md) per Redis  |
| `SESSION_DRIVER` | `database`  | idem                                             |
| `QUEUE_CONNECTION`| `database` | idem                                             |
| `MAIL_MAILER`    | `log`       | impostare su `smtp` quando colleghi un provider  |

### Marcare come "Secret"

In Coolify ogni env var ha una checkbox **Is Secret?**. Attivala per:

- `APP_KEY`
- `DB_PASSWORD`
- `MEILISEARCH_KEY`
- `MAIL_PASSWORD` (quando usato)

Così Coolify offusca il valore nella UI e nei log di build.

---

## 6. Persistent storage

Il compose dichiara 4 volumi (`pgsql-data`, `meilisearch-data`,
`app-storage`, `app-logs`). Coolify li gestisce come named volumes
Docker: li trovi in **Storage → Persistent Storages** sulla risorsa.

**Non** modificare i mount point, specialmente per `pgsql-data`
(`/var/lib/postgresql/data`) e `meilisearch-data` (`/meili_data`): un
path sbagliato = perdita dati al prossimo rebuild.

---

## 7. Deploy e rollout

Tab principale della risorsa → **Deploy**.

Al primo deploy Coolify:

1. clona il repo;
2. esegue `docker compose build` → parte del Dockerfile a 3 stage
   (vendor PHP → build frontend Vite → runtime FrankenPHP). Può
   richiedere 3–5 minuti la prima volta;
3. avvia i container;
4. esegue gli healthcheck; quando `app` è healthy, `worker` e
   `scheduler` partono;
5. l'entrypoint di `app` esegue `php artisan migrate --force` e
   `scout:sync-index-settings`.

Quando tutti i servizi sono verdi, l'app è online al dominio
configurato.

---

## 8. Deploy automatico su push (opzionale)

Nella risorsa: **Webhooks → Enable auto-deploy on push**.

Coolify registra un webhook sul repo GitHub: ad ogni push su
`master` viene avviato un rebuild e un deploy.

Se preferisci controllare manualmente i rilasci, lascia disattivato
e usa il bottone **Deploy** quando vuoi.

---

## 9. Troubleshooting primo deploy

| Sintomo                                     | Azione                                                                 |
| ------------------------------------------- | ---------------------------------------------------------------------- |
| Build fallisce in stage `frontend`          | controlla che `pnpm-lock.yaml` sia committato; log in UI Coolify       |
| `app` unhealthy (`/up` non risponde)        | tab **Logs** del servizio app: probabile errore di config Laravel      |
| Migration fallisce                          | `DB_PASSWORD` non corrisponde, o Postgres non healthy → vedi logs pgsql|
| `MEILISEARCH_KEY` mismatch                  | la key cambiata dopo il primo avvio richiede reindex — vedi [04](04-maintenance.md) |
| 502 Bad Gateway da Traefik                  | il container app non espone la 80, o healthcheck fallisce              |

Il tab **Logs** della risorsa aggrega i log di tutti i container — è
il primo posto da guardare.

Passo successivo: [`03-deployment.md`](03-deployment.md).
