# syntax=docker/dockerfile:1.7

# =============================================================
# Stage 1 — Composer vendor (PHP dependencies, no dev)
# =============================================================
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-interaction \
        --no-progress

# =============================================================
# Stage 2 — Frontend build (Vite + Wayfinder needs PHP CLI)
# =============================================================
FROM node:22-bookworm-slim AS frontend

RUN apt-get update && apt-get install -y --no-install-recommends \
        ca-certificates curl gnupg lsb-release \
    && install -d /etc/apt/keyrings \
    && curl -fsSL https://packages.sury.org/php/apt.gpg -o /etc/apt/keyrings/php.gpg \
    && echo "deb [signed-by=/etc/apt/keyrings/php.gpg] https://packages.sury.org/php/ $(lsb_release -sc) main" \
         > /etc/apt/sources.list.d/php.list \
    && apt-get update \
    && apt-get install -y --no-install-recommends \
        php8.4-cli php8.4-xml php8.4-mbstring php8.4-curl php8.4-intl php8.4-tokenizer \
    && rm -rf /var/lib/apt/lists/*

RUN corepack enable && corepack prepare pnpm@latest --activate

WORKDIR /app

COPY package.json pnpm-lock.yaml ./
RUN pnpm install --frozen-lockfile

COPY --from=vendor /app/vendor ./vendor
COPY . .

RUN pnpm run build

# =============================================================
# Stage 3 — Runtime (FrankenPHP: Caddy + PHP 8.4, single binary)
# =============================================================
FROM dunglas/frankenphp:1-php8.4-bookworm AS runtime

RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip ca-certificates curl \
    && install-php-extensions \
        pdo_pgsql \
        pcntl \
        intl \
        gd \
        zip \
        opcache \
        bcmath \
        exif \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY --from=vendor /app/vendor ./vendor
COPY . .
COPY --from=frontend /app/public/build ./public/build

RUN composer dump-autoload --optimize --no-dev --classmap-authoritative \
    && mkdir -p storage/framework/cache \
                storage/framework/sessions \
                storage/framework/views \
                storage/framework/testing \
                storage/logs \
                storage/app/public \
    && chown -R www-data:www-data /app/storage /app/bootstrap/cache \
    && chmod -R 775 /app/storage /app/bootstrap/cache

COPY deploy/docker/Caddyfile       /etc/caddy/Caddyfile
COPY deploy/docker/php.ini         /usr/local/etc/php/conf.d/zz-app.ini
COPY deploy/docker/entrypoint.sh   /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/entrypoint.sh

ENV SERVER_NAME=":80"

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD curl -fsS http://127.0.0.1/up || exit 1

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
