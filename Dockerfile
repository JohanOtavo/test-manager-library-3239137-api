# Imagen del backend. La compone infra, pero el Dockerfile vive con el código
# que construye.
FROM php:8.3-cli

# pdo_pgsql es la única extensión que hay que añadir; el resto ya viene.
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev unzip \
    && docker-php-ext-install pdo_pgsql

COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY docker/php.ini /usr/local/etc/php/conf.d/library.ini

# Las dependencias primero: así un cambio de código no vuelve a instalarlas.
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-scripts --prefer-dist

COPY . .
RUN composer dump-autoload --optimize

EXPOSE 8000

CMD ["sh", "docker/entrypoint.sh"]
