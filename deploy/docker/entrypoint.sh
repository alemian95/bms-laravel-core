#!/usr/bin/env bash
# Entrypoint condiviso da app/worker/scheduler.
# Il ruolo viene deciso via CONTAINER_ROLE (app|worker|scheduler).
set -euo pipefail

cd /app

# --------------------------------------------------------------
# Storage: ricrea struttura (i volumi partono vuoti al primo deploy)
# --------------------------------------------------------------
mkdir -p storage/framework/cache \
         storage/framework/sessions \
         storage/framework/views \
         storage/framework/testing \
         storage/logs \
         storage/app/public \
         bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

ROLE="${CONTAINER_ROLE:-app}"

# --------------------------------------------------------------
# Cache di framework: rigenerate a ogni boot (config parte dall'env).
# Sicure anche per worker/scheduler — leggono la stessa config.
# --------------------------------------------------------------
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

case "$ROLE" in
	app)
		echo "[entrypoint] role=app"

		if [ "${APP_RUN_MIGRATIONS:-true}" = "true" ]; then
			echo "[entrypoint] migrate --force"
			php artisan migrate --force --no-interaction
		fi

		if [ "${APP_SCOUT_SYNC:-true}" = "true" ]; then
			echo "[entrypoint] scout:sync-index-settings"
			php artisan scout:sync-index-settings --no-interaction || true
		fi

		echo "[entrypoint] storage:link"
		php artisan storage:link 2>/dev/null || true
		;;
	worker)
		echo "[entrypoint] role=worker"
		;;
	scheduler)
		echo "[entrypoint] role=scheduler"
		;;
	*)
		echo "[entrypoint] ERRORE: CONTAINER_ROLE='$ROLE' non valido (attesi: app|worker|scheduler)" >&2
		exit 1
		;;
esac

echo "[entrypoint] exec: $*"
exec "$@"
