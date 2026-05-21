# =============================================================================
# Stage 1 — Compilar assets frontend (Tailwind + Vite)
# Usa mirror de npm para redes con acceso restringido a Fastly CDN
# =============================================================================
FROM node:20-alpine AS node-builder

WORKDIR /app
COPY package*.json ./
RUN npm config set registry https://registry.npmmirror.com && \
    npm ci --silent 2>/dev/null || npm install --silent

COPY vite.config.js ./
COPY resources/ resources/
RUN npm run build

# =============================================================================
# Stage 2 — Instalar dependencias PHP (sin dev)
# =============================================================================
FROM composer:2.8 AS php-builder

WORKDIR /app
COPY composer.json composer.lock* ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --ignore-platform-reqs \
    --prefer-dist \
    --quiet

COPY . .
RUN composer dump-autoload --no-dev --optimize --quiet

# =============================================================================
# Stage 3 — Imagen de producción: nginx + php-fpm + supervisord
# =============================================================================
FROM php:8.2-fpm-alpine AS runtime

LABEL description="Gestor centralizado de usuarios LDAP" version="1.0"

# ── Sistema ───────────────────────────────────────────────────────────────────
RUN apk add --no-cache \
    nginx supervisor openldap-dev openldap-clients \
    libpng-dev libjpeg-turbo-dev freetype-dev libzip-dev \
    icu-dev oniguruma-dev libxml2-dev \
    netcat-openbsd bash curl

# ── Extensiones PHP ───────────────────────────────────────────────────────────
RUN docker-php-ext-configure ldap \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j"$(nproc)" \
    pdo_mysql ldap gd zip intl mbstring xml bcmath opcache pcntl

# ── Configuración ─────────────────────────────────────────────────────────────
COPY docker/php/php.ini        /usr/local/etc/php/conf.d/zzz-custom.ini
COPY docker/nginx/nginx.conf   /etc/nginx/nginx.conf
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf   /etc/supervisor/conf.d/supervisord.conf

# ── Código de la aplicación ───────────────────────────────────────────────────
WORKDIR /var/www/html
COPY --chown=www-data:www-data . .
COPY --from=php-builder --chown=www-data:www-data /app/vendor ./vendor
COPY --from=php-builder --chown=www-data:www-data /app/composer.json ./composer.json
COPY --from=node-builder --chown=www-data:www-data /app/public/build ./public/build

# ── Limpiar ficheros no necesarios ────────────────────────────────────────────
RUN rm -f database/database.sqlite resources/views/welcome.blade.php \
          public/icons/velxio.png phpunit.xml .editorconfig \
          .gitattributes vite.config.js package*.json \
          bundle.sh migrate-to-docker.sh

# ── Directorios de runtime ────────────────────────────────────────────────────
RUN mkdir -p storage/logs storage/framework/{cache/data,sessions,views} \
             storage/app/public bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache

# Guardar copia de public/ para poblar volumen en primer arranque
RUN cp -a /var/www/html/public /srv/public-seed

# ── Entrypoint ────────────────────────────────────────────────────────────────
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80

HEALTHCHECK --interval=15s --timeout=5s --start-period=90s --retries=5 \
    CMD curl -fsS http://localhost/login > /dev/null || exit 1

ENTRYPOINT ["/entrypoint.sh"]
