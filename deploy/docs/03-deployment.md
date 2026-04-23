# 03 — Deploy e aggiornamenti

Come l'app viene effettivamente rilasciata e aggiornata dopo il primo
setup.

---

## 1. Flusso di deploy

Ogni deploy in Coolify (manuale o via webhook) esegue questi step,
tutti automatici:

```
 git pull
   │
   ▼
 docker compose build          (deploy/Dockerfile, 3 stage)
   │   ├─ vendor   composer install --no-dev
   │   ├─ frontend pnpm install + pnpm run build  (Wayfinder → Vite)
   │   └─ runtime  FrankenPHP + PHP extensions + dump-autoload ottimizzato
   ▼
 docker compose up -d          (rolling restart per-servizio)
   │
   ▼
 entrypoint.sh (container app)
   ├─ config:cache, route:cache, view:cache, event:cache
   ├─ migrate --force
   ├─ scout:sync-index-settings
   └─ storage:link
   │
   ▼
 Healthcheck /up → servizio "healthy" → worker+scheduler partono
```

Tutto gira **dentro la VPS**: Coolify non pusha immagini su un
registry esterno (a meno che tu non lo configuri). L'immagine resta
locale, referenziata dal tag `bms-core-app:latest`.

---

## 2. Aggiornare l'applicazione

### 2.1 Aggiornamento da repository Git

1. Push del codice sul branch `master`.
2. Se l'auto-deploy è attivo: Coolify riceve il webhook e parte.
3. Se non è attivo: Coolify UI → risorsa → **Redeploy**.

Coolify esegue un **rolling restart**: Traefik smette di inoltrare
traffico al vecchio container `app`, avvia il nuovo, quando è healthy
commuta il traffico e ferma il vecchio. Zero downtime in condizioni
normali.

### 2.2 Cosa succede alle migrazioni

L'entrypoint esegue `php artisan migrate --force` a ogni boot del
container `app`. Se un deploy introduce una migrazione:

- Container nuovo parte → esegue la migrazione → healthcheck OK →
  traffico commutato.
- Se la migrazione fallisce, il container non diventa healthy, il
  vecchio continua a servire: il deploy risulta fallito, ma l'app
  resta online.

**Migrazioni pericolose** (drop di colonna, rename, data migration
grossa): per sicurezza disabilita `APP_RUN_MIGRATIONS` in Coolify
→ fai deploy → esegui la migrazione manualmente via terminal del
container (vedi [4. Comandi artisan one-shot](#4-comandi-artisan-one-shot))
→ riabilita il flag.

### 2.3 Asset frontend

Vite genera hash unici per ogni asset a ogni build (cache-busting
automatico). Non serve svuotare cache CDN: i browser caricano i nuovi
hash.

Se vedi il frontend "vecchio" dopo un deploy: hard reload
(Cmd/Ctrl + Shift + R) o cache Traefik locale — in genere transitoria.

---

## 3. Rollback

Opzioni dalla più veloce alla più invasiva:

### 3.1 Rollback via Coolify

**Deployments** tab della risorsa: ogni deploy è elencato con lo SHA
del commit. Click su un deploy precedente → **Redeploy** ripristina
quella versione (rebuildata dal commit).

### 3.2 Rollback via Git revert

```bash
# in locale
git revert <sha-problematico>
git push origin master
```

Coolify rilancia il deploy col commit revertato. **Consigliato** in
presenza di migrazioni, perché `git revert` ti costringe a creare
anche la migrazione inversa (scrittura manuale).

### 3.3 Rollback invasivo (solo ultima spiaggia)

Se l'app è inaccessibile e il rollback Coolify non parte, via SSH:

```bash
cd /data/coolify/applications/<risorsa-id>
docker compose down
git checkout <sha-buono>
docker compose up -d --build
```

Il path esatto della risorsa è visibile nella UI Coolify → tab
**Configuration**.

---

## 4. Comandi artisan one-shot

Per eseguire un comando artisan nel container live:

Dalla UI Coolify → servizio **app** → tab **Terminal**. Shell
interattiva dentro il container.

Esempi comuni:

```bash
php artisan tinker
php artisan scout:import "App\Models\Bookmark"
php artisan queue:retry all
php artisan cache:clear
php artisan migrate:status
```

In alternativa via SSH:

```bash
docker ps                                 # trova il container app
docker exec -it <container_id> bash
```

---

## 5. Reindex Meilisearch

Se cambi `MEILISEARCH_KEY`, modifichi `toSearchableArray()`, o
l'indice si corrompe:

```bash
# dentro il container app
php artisan scout:flush "App\Models\Bookmark"
php artisan scout:import "App\Models\Bookmark"
```

Il `scout:sync-index-settings` parte da solo a ogni deploy, non serve
rilanciarlo.

---

## 6. Scalare i worker queue

Coolify UI → servizio **worker** → tab **Advanced** → **Replicas** →
alza a 2 o più. Attenzione: ogni worker mangia CPU/RAM (Laravel boot
~80 MB ciascuno). Su una VPS piccola, tieniti largo.

Alternativa: aggiungere flag sul comando per più processi dentro lo
stesso worker (sconsigliato in compose semplice, richiede
supervisord).
