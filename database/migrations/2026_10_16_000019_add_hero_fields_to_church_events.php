<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Portada del evento personalizable: texto y botón opcionales, y la opción de
 * ocultar los textos cuando la imagen ya los trae (p. ej. un afiche).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('church_events', function (Blueprint $table) {
            $table->string('hero_text', 255)->nullable()->after('image_alt');
            $table->string('hero_button_label', 50)->nullable()->after('hero_text');
            $table->string('hero_button_url', 500)->nullable()->after('hero_button_label');
            $table->boolean('hero_show_text')->default(true)->after('hero_button_url');
        });
    }

    public function down(): void
    {
        Schema::table('church_events', function (Blueprint $table) {
            $table->dropColumn(['hero_text', 'hero_button_label', 'hero_button_url', 'hero_show_text']);
        });
    }
};
