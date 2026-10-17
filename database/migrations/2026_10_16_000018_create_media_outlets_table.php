<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Medios del movimiento (Bethel TV, Bethel Radio, Impacto…) en la portada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_outlets', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('url', 500);
            $table->string('image_path')->nullable();
            $table->string('image_alt')->nullable();
            $table->string('background_color', 7)->nullable();
            $table->boolean('is_published')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_published', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_outlets');
    }
};
