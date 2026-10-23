<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * T-02 · Migración inicial.
 *
 * El motor llega vacío y todo el DDL vive aquí (artículo V, ADR-001). Ninguna
 * columna lleva DEFAULT: los valores los pone el dominio (data-model §3).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('create schema if not exists library');

        Schema::create('library.category', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name', 120)->unique();
        });

        Schema::create('library.user', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('username', 120)->unique();
            // Nunca se indexa: no se busca por hash (data-model §7).
            $table->string('password_hash', 512);
            $table->string('role', 40);
        });

        Schema::create('library.member', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('full_name', 200);
            $table->string('document_number', 30)->unique();
        });

        Schema::create('library.book', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('title', 250);
            $table->string('author', 200);
            $table->char('isbn', 13);
            $table->uuid('category_id');
            $table->integer('total_copies');
            $table->integer('available_copies');
            $table->string('cover_key', 512)->nullable();
            $table->timestampTz('deleted_at')->nullable();

            $table->foreign('category_id')->references('id')->on('library.category')->restrictOnDelete();
        });

        Schema::create('library.loan', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('member_id');
            $table->uuid('loaned_by_user_id');
            $table->timestampTz('loaned_at');

            $table->foreign('member_id')->references('id')->on('library.member')->restrictOnDelete();
            $table->foreign('loaned_by_user_id')->references('id')->on('library.user')->restrictOnDelete();
            $table->index('loaned_at');
        });

        Schema::create('library.loan_item', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('loan_id');
            $table->uuid('book_id');
            // Valores congelados del instante del préstamo (artículo VIII).
            $table->string('book_title', 250);
            $table->char('isbn', 13);
            // Sin FK a propósito: la categoría del reporte no sigue al catálogo.
            $table->string('category_name', 120);
            $table->timestampTz('due_at');
            $table->timestampTz('returned_at')->nullable();

            $table->foreign('loan_id')->references('id')->on('library.loan')->cascadeOnDelete();
            $table->foreign('book_id')->references('id')->on('library.book')->restrictOnDelete();
            $table->unique(['loan_id', 'book_id']);
            $table->index('book_id');
        });

        $this->addEngineRules();
        $this->seedCategories();
    }

    public function down(): void
    {
        Schema::dropIfExists('library.loan_item');
        Schema::dropIfExists('library.loan');
        Schema::dropIfExists('library.book');
        Schema::dropIfExists('library.member');
        Schema::dropIfExists('library.user');
        Schema::dropIfExists('library.category');
    }

    /** Lo que el motor puede imponer, lo impone el motor (artículo VI). */
    private function addEngineRules(): void
    {
        // Última barrera de la concurrencia: si salta, algo escribió fuera del
        // adaptador (ADR-002).
        DB::statement(
            'alter table library.book add constraint ck_book_available_range '
            .'check (available_copies >= 0 and available_copies <= total_copies)'
        );

        // El ISBN es único solo entre libros activos: uno dado de baja libera
        // su número (CA-03.2).
        DB::statement(
            'create unique index ux_book_isbn_active on library.book (isbn) where deleted_at is null'
        );

        // Índices de los dos accesos más frecuentes (data-model §4).
        DB::statement(
            'create index ix_book_category_title_active on library.book (category_id, title) '
            .'where deleted_at is null'
        );
        DB::statement(
            'create index ix_loan_item_due_pending on library.loan_item (due_at) where returned_at is null'
        );
    }

    /** Las cinco categorías con identificadores fijos (data-model §9). */
    private function seedCategories(): void
    {
        DB::table('library.category')->insert([
            ['id' => '11111111-1111-4111-8111-111111111111', 'name' => 'Novela'],
            ['id' => '22222222-2222-4222-8222-222222222222', 'name' => 'Ciencia'],
            ['id' => '33333333-3333-4333-8333-333333333333', 'name' => 'Tecnología'],
            ['id' => '44444444-4444-4444-8444-444444444444', 'name' => 'Historia'],
            ['id' => '55555555-5555-4555-8555-555555555555', 'name' => 'Infantil'],
        ]);
    }
};
