<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * T-18 · Baja a la base dos invariantes que data-model §4 marcaba como "solo
 * dominio": total de ejemplares positivo y rol dentro de la lista.
 *
 * Una regla que solo vive en código protege a la aplicación, no a los datos
 * (artículo VI). Con esto, un INSERT a mano por psql que las viole falla.
 *
 * Al aplicarse, las dos filas de data-model §4 pasan de "pendiente (T-18)" a
 * "motor"; la nota queda en docs/spec-laravel/traduccion.md porque el spec
 * original no se edita.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            'alter table library.book add constraint ck_book_total_positive check (total_copies >= 1)'
        );

        DB::statement(
            "alter table library.user add constraint ck_user_role check (role in ('admin', 'librarian'))"
        );
    }

    public function down(): void
    {
        DB::statement('alter table library.book drop constraint ck_book_total_positive');
        DB::statement('alter table library.user drop constraint ck_user_role');
    }
};
