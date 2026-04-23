# 04 — Manutenzione: backup, log, monitoring, troubleshooting

---

## 1. Backup PostgreSQL

**Critico**: senza backup, una corruzione o un comando sbagliato è
definitivo.

### 1.1 Backup automatici con Coolify

Coolify supporta backup schedulati per database aggiunti come
"Database Resource", ma nel nostro compose Postgres è dentro la
risorsa app — Coolify lo tratta come container generico.

Due alternative:

#### Opzione A — Dump schedulato via host cron

Dump giornaliero dal container Postgres in una cartella della VPS,
poi sync off-site (S3, Backblaze B2, ecc.).

```bash
sudo crontab -e
```

Aggiungi:

```
0 3 * * * docker exec $(docker ps -qf name=pgsql) pg_dump -U bms_core bms_core | gzip > /var/backups/bms-core/pg_$(date +\%F).sql.gz
```

Prepara la cartella e la policy di rotazione:

```bash
sudo mkdir -p /var/backups/bms-core
sudo find /var/backups/bms-core -name "pg_*.sql.gz" -mtime +14 -delete   # retention 14gg
```

**Off-site**: configura `rclone` per sincronizzare `/var/backups/bms-core`
su un bucket remoto. Senza off-site i backup non proteggono dal
failure della VPS stessa.

#### Opzione B — Spostare pgsql in risorsa Coolify dedicata

Appena lo stack si stabilizza, conviene trasformare Postgres in una
"Database Resource" Coolify (UI → New → Database → PostgreSQL):
Coolify gestisce backup schedulati, retention e restore con pochi
click, e la risorsa app lo referenzia via env var.

Questa migrazione richiede:

1. dump del DB attuale
2. creazione della risorsa database in Coolify
3. restore del dump
4. aggiornamento `DB_HOST` nelle env var dell'app (punta al nuovo
   hostname interno generato da Coolify)
5. rimozione del servizio `pgsql` dal compose

Step dettagliati: [05-evolving.md § Migrare Postgres a risorsa
Coolify](05-evolving.md#migrare-postgres-a-risorsa-coolify-dedicata).

### 1.2 Ripristino

```bash
# copia il dump dentro il container
docker cp pg_backup.sql.gz <container_pgsql>:/tmp/
docker exec -it <container_pgsql> bash
gunzip /tmp/pg_backup.sql.gz
psql -U bms_core -d bms_core < /tmp/pg_backup.sql
```

**Testa il restore ALMENO una volta** su un DB di prova. Un backup
mai testato è un backup che non esiste.

---

## 2. Backup Meilisearch

Meilisearch supporta snapshot nativi. Per l'entità del progetto, il
dato più importante è comunque il DB (gli indici Scout si possono
ricostruire con `scout:import`).

Se vuoi backup anche degli indici:

```bash
docker exec <container_meilisearch> curl -X POST http://localhost:7700/snapshots \
  -H "Authorization: Bearer $MEILISEARCH_KEY"
```

Gli snapshot finiscono in `/meili_data/snapshots` (volume
`meilisearch-data`).

**Alternativa pragmatica**: se perdi l'indice, reindex da codice.

```bash
php artisan scout:import "App\Models\Bookmark"
```

---

## 3. Backup volume `app-storage`

Contiene upload utente e file generati runtime (poco finché non
aggiungi feature di upload).

```bash
# nel cron host, accanto al dump Postgres
docker run --rm -v app-storage:/data -v /var/backups/bms-core:/backup \
  alpine tar czf /backup/app-storage_$(date +%F).tgz -C /data .
```

---

## 4. Log

### 4.1 Dove sono

- Log applicativi Laravel: stream su **stderr** (config `LOG_CHANNEL=stderr`),
  visibili da Coolify UI → servizio → **Logs**.
- Log Caddy/FrankenPHP: anche questi su stderr, mischiati.
- Log queue/scheduler: sul container corrispondente.

### 4.2 Visualizzazione rapida

Via Coolify UI: il tab Logs di ogni servizio ha filtro testo e
auto-refresh.

Via SSH:

```bash
docker ps
docker logs -f --tail=200 <container_id>
```

Tutti i servizi insieme:

```bash
cd /data/coolify/applications/<risorsa-id>
docker compose logs -f --tail=200
```

### 4.3 Retention

Di default Docker fa log rotation ma senza limite di dimensione. Se
la VPS è piccola conviene imporre un cap globale:

```bash
sudo tee /etc/docker/daemon.json <<'EOF'
{
  "log-driver": "json-file",
  "log-opts": {
    "max-size": "20m",
    "max-file": "5"
  }
}
EOF
sudo systemctl restart docker
```

Applica a tutti i container (vecchi e nuovi). Verifica che Coolify
resti up dopo il restart (a volte serve un `docker compose up -d`
nella sua directory).

---

## 5. Monitoring base

Per cominciare non serve Prometheus/Grafana. Basta:

- **Coolify UI → Server → Dashboard**: CPU, RAM, disco, container
  attivi.
- **Healtcheck** `/up`: se rosso in Coolify, ricevi una notifica
  (configura un canale notifiche: Settings → Notifications → Email /
  Discord / Slack / Telegram).

Quando l'app cresce:

- **Uptime Kuma** (altra risorsa Coolify, 30 secondi di setup):
  ping al dominio pubblico ogni N minuti → alert email/Telegram.
- Sentry o Bugsnag per application errors (Laravel ha reporter
  integrati).

---

## 6. Aggiornamenti sistema

### 6.1 Kernel / pacchetti Ubuntu

Gestiti da `unattended-upgrades`. Per reboot dopo kernel update:

```bash
sudo apt install needrestart
sudo needrestart        # ti dice cosa richiede reboot
sudo reboot             # quando decidi tu
```

### 6.2 Coolify

Coolify si auto-aggiorna se attivi **Settings → Advanced → Auto
Update**. Altrimenti dalla dashboard c'è un banner "new version
available" con un click.

### 6.3 Immagini Docker dei servizi

- **PostgreSQL**: ancorato a `postgres:18-alpine`. Per passare a 19
  quando uscirà, **fai prima il dump**, cambia il tag nel compose,
  redeploy, verifica, poi elimina il dump vecchio.
- **Meilisearch**: ancorato a `v1.10`. Breaking changes possibili
  tra minor: leggi changelog. Per sicurezza fai snapshot prima.
- **FrankenPHP base image**: aggiorna il tag in `deploy/Dockerfile`
  quando esce una nuova versione LTS.

---

## 7. Troubleshooting

| Problema                                | Prima cosa da guardare                                   |
| --------------------------------------- | -------------------------------------------------------- |
| 502 Bad Gateway dal dominio             | Coolify Logs del servizio `app`: crash o healthcheck KO  |
| "Too many connections" Postgres         | worker che si moltiplicano? riduci replicas o riavvia    |
| Queue job non processati                | `docker logs worker`; `php artisan queue:failed`         |
| Scheduler non parte                     | log del servizio `scheduler`; verifica non sia down      |
| Disco pieno                             | `docker system df` → `docker system prune -a` (attento!) |
| Meilisearch non risponde                | container healthy? `MEILISEARCH_KEY` corretto nell'app?  |
| Build lentissimo                        | vedi sotto § 8                                           |

---

## 8. Performance di build

Il build frontend Vite + PHP è la fase più lenta. Ottimizzazioni:

- Coolify abilita il **BuildKit cache** di default: le dipendenze
  vendor/node non si ri-scaricano se `composer.lock` / `pnpm-lock.yaml`
  non sono cambiati.
- Se il build va in OOM: serve più RAM (o swap temporaneo). Su VPS
  da 2 GB aggiungi 2 GB di swap:

```bash
sudo fallocate -l 2G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
```

- Build più lunghi di 10 min: valuta un registry esterno (GitHub
  Container Registry) e push da CI, così la VPS fa solo pull.
