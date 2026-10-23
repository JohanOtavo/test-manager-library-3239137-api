# test-manager-library-3239137-api

Backend de **Manager Library** en PHP con Laravel. Implementa el spec de
[`test-manager-library-3239137-docs`](https://github.com/code-sena/test-manager-library-3239137-docs)
traducido de Python/.NET a Laravel, respetando el negocio tal cual: historias,
criterios de aceptación, reglas, contrato de la API y modelo de datos.

## Cómo está organizado

El spec exige separar capas y lo verifica, así que las carpetas son las capas:

| Carpeta | Qué vive ahí | Qué puede importar |
|---|---|---|
| `app/Domain` | Entidades, objetos de valor y reglas de negocio | Nada: solo PHP (artículo I) |
| `app/Application` | Casos de uso y los **puertos** que necesitan del exterior | Solo el dominio (artículo II) |
| `app/Adapters` | Persistencia, seguridad, portadas, HTTP | Lo que haga falta |
| `app/Providers` | `PortBindingsProvider`: el único punto de composición | Todo (artículo III) |

`tests/Architecture/LayerDependenciesTest.php` lee los archivos y falla si
alguien rompe esa dirección. Es el equivalente al contrato de `import-linter`
que pide el spec en Python.

## Puesta en marcha

```bash
cp .env.example .env     # rellenar JWT_SECRET, ADMIN_USERNAME, ADMIN_PASSWORD
composer install
php artisan key:generate
php artisan migrate      # el esquema lo crean las migraciones, no infra
php artisan library:ensure-admin
php artisan serve
```

La base arranca **vacía**: `infra` levanta el motor y nada más (artículo V).
El administrador inicial no se siembra en SQL porque el hash solo lo produce el
puerto de hash, así que lo crea `library:ensure-admin` leyendo el entorno.
Laravel guarda el registro de migraciones en `public.migrations`; el esquema
`library` conserva únicamente las seis tablas del modelo de negocio.

## Pruebas

```bash
vendor/bin/phpunit
```

| Suite | Qué comprueba | Contra qué |
|---|---|---|
| `dominio` | Invariantes: ISBN, ejemplares, 14 días, máximo 3, estado calculado | Nada: objetos puros |
| `aplicacion` | Prestar y devolver, incluida la carrera por el último ejemplar | Dobles de prueba |
| `adaptadores` | Firma y vencimiento de JWT, lectura de multipart para portadas | Dobles de prueba |
| `arquitectura` | Artículos I, II y III | Los propios archivos |

## Decisiones propias que el spec dejaba abiertas

1. **Concurrencia sin columna de versión.** `plan.md` D-04 y ADR-002 piden un
   testigo de versión por libro, pero `data-model.md` §3 no lista esa columna y
   el documento manda (artículo X). Se usa `available_copies` como testigo: el
   `UPDATE` lleva `where available_copies = <lo que se leyó>`, así que de dos
   bibliotecarios peleando por el último ejemplar uno recibe 201 y el otro 409.
   El `CHECK ck_book_available_range` sigue siendo la última barrera.
2. **Argon2id con respaldo.** Se usa `PASSWORD_ARGON2ID` cuando el PHP del
   contenedor lo trae compilado y bcrypt si no (D-09). El dominio nunca ve la
   clave en claro en ningún caso.
3. **JWT a mano.** Son tres piezas en base64url y un HMAC; añadir una
   dependencia para eso no se justificaba.
