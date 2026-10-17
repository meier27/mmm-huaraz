<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bloques de la página «Nosotros»: foto + texto (blanco o destacado) y citas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('about_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20);
            $table->text('body');
            $table->string('author_name', 150)->nullable();
            $table->string('author_role', 150)->nullable();
            $table->string('image_path')->nullable();
            $table->string('image_alt')->nullable();
            $table->boolean('is_published')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_published', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('about_blocks');
    }
};
