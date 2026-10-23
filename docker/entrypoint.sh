#!/bin/sh
set -e

# La aplicación no usa cifrado de sesión ni cookies, pero Laravel espera una
# clave: si el entorno no la trae, se genera una de usar y tirar para este
# contenedor. Nunca se escribe en el repositorio (artículo IX).
if [ -z "$APP_KEY" ]; then
    APP_KEY="base64:$(head -c 32 /dev/urandom | base64)"
    export APP_KEY
fi

# El esquema es propiedad de las migraciones (artículo V, ADR-001).
php artisan migrate --force

# El administrador inicial sale del entorno porque el hash solo lo produce el
# puerto de hash (data-model §9).
php artisan library:ensure-admin

exec php artisan serve --host=0.0.0.0 --port=8000
