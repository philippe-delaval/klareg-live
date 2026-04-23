# syntax=docker/dockerfile:1.7
# ──────────────────────────────────────────────────────────────────────
# Klareg Live — production image
# One image, three roles controlled by CMD:
#   supervisord  → nginx + php-fpm (HTTP / Filament / API / OAuth / overlays)
#   php artisan reverb:start
#   php artisan twitch:eventsub
#   php artisan twitch:irc
# ──────────────────────────────────────────────────────────────────────

# ─── Stage 1: PHP vendor (needed by Vite for Filament CSS) ───────────
FROM composer:2 AS vendor
WORKDIR /app
COPY backend/composer.json backend/composer.lock ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-interaction \
        --ignore-platform-reqs

# ─── Stage 2: build Filament/Vite assets ──────────────────────────────
FROM node:22-alpine AS assets
WORKDIR /build
COPY backend/package.json backend/package-lock.json* backend/vite.config.js ./
RUN if [ -f package-lock.json ]; then \
        npm ci --no-audit --no-fund; \
    else \
        npm install --no-audit --no-fund; \
    fi
COPY backend/resources ./resources
COPY backend/public ./public
COPY --from=vendor /app/vendor/filament ./vendor/filament
RUN npm run build

# ─── Stage 2: runtime ────────────────────────────────────────────────
FROM php:8.4-fpm-alpine AS runtime

RUN apk add --no-cache \
        nginx \
        supervisor \
        bash \
        git \
        unzip \
        tini \
        icu-dev \
        libzip-dev \
        oniguruma-dev \
        postgresql-dev \
        sqlite-dev \
        linux-headers \
        $PHPIZE_DEPS \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        intl \
        mbstring \
        opcache \
        pcntl \
        pdo_pgsql \
        pdo_sqlite \
        sockets \
        zip \
    && apk del $PHPIZE_DEPS \
    && rm -rf /var/cache/apk/* /tmp/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Install PHP dependencies without the app code first — caches well.
COPY backend/composer.json backend/composer.lock ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-interaction

# App code + built assets + overlays.
COPY backend/ ./
COPY --from=assets /build/public/build ./public/build
COPY overlay ./public/overlay

# Now finalise composer (runs package discovery, optimised autoloader).
RUN composer dump-autoload --optimize --no-dev \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Runtime configs.
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/site.conf /etc/nginx/http.d/default.conf
COPY docker/php/overrides.ini /usr/local/etc/php/conf.d/zz-overrides.ini
COPY docker/php/fpm-pool.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

# 80  → nginx (web role)
# 8080 → Laravel Reverb (reverb role)
EXPOSE 80 8080

ENTRYPOINT ["/sbin/tini", "--", "/usr/local/bin/entrypoint"]
CMD ["supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
