<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migración inicial (T-02): esquema library, seis tablas, restricciones y semilla de categorías.
 * Ninguna columna lleva DEFAULT: los valores los pone el dominio (data-model.md §3).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE SCHEMA IF NOT EXISTS library');

        Schema::create('category', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 120)->unique();
        });

        Schema::create('book', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title', 250);
            $table->string('author', 200);
            $table->char('isbn', 13);
            $table->uuid('category_id');
            $table->integer('total_copies');
            $table->integer('available_copies');
            $table->string('cover_key', 512)->nullable();
            $table->timestampTz('deleted_at')->nullable();
            // Testigo de concurrencia optimista (ADR-002, spec-review.md E-01)
            $table->integer('version');

            $table->foreign('category_id')->references('id')->on('category')->restrictOnDelete();
        });

        Schema::create('member', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('full_name', 200);
            $table->string('document_number', 30)->unique();
        });

        Schema::create('user', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('username', 120)->unique();
            $table->string('password_hash', 512);
            $table->string('role', 40);
        });

        Schema::create('loan', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('member_id');
            $table->uuid('loaned_by_user_id');
            $table->timestampTz('loaned_at');

            $table->foreign('member_id')->references('id')->on('member')->restrictOnDelete();
            $table->foreign('loaned_by_user_id')->references('id')->on('user')->restrictOnDelete();
            $table->index('loaned_at');
        });

        Schema::create('loan_item', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('loan_id');
            $table->uuid('book_id');
            // Valores congelados en el instante del préstamo; category_name sin FK a propósito (ADR-004)
            $table->string('book_title', 250);
            $table->char('isbn', 13);
            $table->string('category_name', 120);
            $table->timestampTz('due_at');
            $table->timestampTz('returned_at')->nullable();

            // RESTRICT en las dos: los préstamos no se borran (spec-review.md C-02)
            $table->foreign('loan_id')->references('id')->on('loan')->restrictOnDelete();
            $table->foreign('book_id')->references('id')->on('book')->restrictOnDelete();
            $table->unique(['loan_id', 'book_id']);
            $table->index('book_id');
        });

        // Invariantes que la base puede imponer (artículo VI, spec-review.md C-01)
        DB::statement('ALTER TABLE book ADD CONSTRAINT ck_book_available_range CHECK (available_copies BETWEEN 0 AND total_copies)');
        DB::statement('ALTER TABLE book ADD CONSTRAINT ck_book_total_positive CHECK (total_copies >= 1)');
        DB::statement("ALTER TABLE book ADD CONSTRAINT ck_book_isbn_digits CHECK (isbn ~ '^[0-9]{13}$')");
        DB::statement("ALTER TABLE book ADD CONSTRAINT ck_book_cover_key_not_empty CHECK (cover_key <> '')");
        DB::statement("ALTER TABLE \"user\" ADD CONSTRAINT ck_user_role CHECK (role IN ('admin', 'librarian'))");
        DB::statement('ALTER TABLE "user" ADD CONSTRAINT ck_user_username_normalized CHECK (username = lower(btrim(username)))');

        // ISBN único solo entre libros activos, así uno dado de baja puede volver a registrarse (ADR-003)
        DB::statement('CREATE UNIQUE INDEX ux_book_isbn_active ON book (isbn) WHERE deleted_at IS NULL');
        // Índices de acceso de data-model.md §4
        DB::statement('CREATE INDEX ix_book_category_title_active ON book (category_id, title) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX ix_loan_item_due_at_pending ON loan_item (due_at) WHERE returned_at IS NULL');

        // Las cinco categorías fijas con sus identificadores literales (data-model.md §9)
        DB::table('category')->insert([
            ['id' => '11111111-1111-4111-8111-111111111111', 'name' => 'Novela'],
            ['id' => '22222222-2222-4222-8222-222222222222', 'name' => 'Ciencia'],
            ['id' => '33333333-3333-4333-8333-333333333333', 'name' => 'Tecnología'],
            ['id' => '44444444-4444-4444-8444-444444444444', 'name' => 'Historia'],
            ['id' => '55555555-5555-4555-8555-555555555555', 'name' => 'Infantil'],
        ]);
    }

    public function down(): void
    {
        DB::statement('DROP SCHEMA IF EXISTS library CASCADE');
    }
};
