# ---------------------------------------------------------------------------
# Estágio 1 — assets do front-end (Tailwind + Alpine via Vite)
# ---------------------------------------------------------------------------
FROM node:22 AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --ignore-scripts

COPY vite.config.js tailwind.config.js postcss.config.js ./
COPY resources ./resources
COPY app ./app
RUN npm run build


# ---------------------------------------------------------------------------
# Estágio 2 — dependências PHP
# ---------------------------------------------------------------------------
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
# --no-scripts porque os scripts do Laravel precisam do código da aplicação,
# que só é copiado depois. O package:discover roda no estágio final.
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-interaction \
        --prefer-dist \
        --optimize-autoloader


# ---------------------------------------------------------------------------
# Estágio 3 — imagem final
# ---------------------------------------------------------------------------
FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
        nginx \
        supervisor \
        gettext \
        libpng \
        libjpeg-turbo \
        freetype \
        icu-libs \
        postgresql-libs \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        icu-dev \
        postgresql-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" gd intl pdo_pgsql opcache \
    && apk del .build-deps

# OPcache: sem isso o Laravel recompila cada arquivo PHP a cada requisição.
RUN { \
        echo 'opcache.enable=1'; \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.max_accelerated_files=10000'; \
        echo 'opcache.validate_timestamps=0'; \
    } > /usr/local/etc/php/conf.d/opcache.ini

# O upload da ficha assinada aceita até 5 MB; a margem cobre o overhead do multipart.
RUN { \
        echo 'upload_max_filesize=8M'; \
        echo 'post_max_size=10M'; \
        echo 'memory_limit=256M'; \
        echo 'expose_php=Off'; \
    } > /usr/local/etc/php/conf.d/app.ini

WORKDIR /var/www/html

COPY --from=vendor /app/vendor ./vendor
COPY . .
COPY --from=assets /app/public/build ./public/build

COPY docker/nginx.conf.template /etc/nginx/nginx.conf.template
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/start.sh /usr/local/bin/start
RUN chmod +x /usr/local/bin/start

# O binário do composer vem do estágio anterior só para gerar o classmap das
# classes de App\ (o vendor foi instalado antes de o código existir), e sai em
# seguida — produção não precisa dele. --no-scripts porque o package:discover
# depende das variáveis de ambiente, que só existem em tempo de execução.
COPY --from=vendor /usr/bin/composer /usr/local/bin/composer
RUN composer dump-autoload --optimize --no-dev --no-interaction --no-scripts \
    && rm /usr/local/bin/composer \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views \
                storage/logs storage/app/private bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache \
    # Os workers do nginx rodam como www-data e precisam dos diretórios de temp.
    && mkdir -p /var/lib/nginx/tmp /var/log/nginx \
    && chown -R www-data:www-data /var/lib/nginx /var/log/nginx

# O Render injeta a porta em $PORT; 8080 é só o padrão local.
ENV PORT=8080
EXPOSE 8080

CMD ["start"]
