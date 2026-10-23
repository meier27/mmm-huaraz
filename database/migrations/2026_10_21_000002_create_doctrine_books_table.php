<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Doctrinario en el lector propio: el PDF y sus páginas (imágenes) se guardan
 * en el disco privado; las páginas se sirven solo con enlaces firmados.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctrine_books', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->string('author', 150)->nullable();
            $table->string('pdf_path');
            $table->unsignedSmallInteger('page_count')->default(0);
            $table->unsignedSmallInteger('page_width')->default(0);
            $table->unsignedSmallInteger('page_height')->default(0);
            $table->string('status', 20)->default('processing'); // processing | ready | failed
            $table->string('error', 500)->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctrine_books');
    }
};
