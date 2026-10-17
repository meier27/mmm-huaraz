<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lemas anuales del movimiento. La portada muestra el más reciente publicado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mottos', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year')->unique();
            $table->string('title', 100);
            $table->text('description')->nullable();
            $table->string('verse_text', 255)->nullable();
            $table->string('verse_reference', 100)->nullable();
            $table->string('image_path')->nullable();
            $table->string('image_alt')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->index(['is_published', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mottos');
    }
};
