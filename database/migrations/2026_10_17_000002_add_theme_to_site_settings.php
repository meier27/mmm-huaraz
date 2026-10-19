<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Apariencia editable desde el panel: colores principales y fuentes.
 * Vacío = el valor predeterminado de resources/css/app.css.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->char('color_primary', 7)->nullable();
            $table->char('color_accent', 7)->nullable();
            $table->char('color_button', 7)->nullable();
            $table->char('color_background', 7)->nullable();
            $table->string('font_body', 30)->nullable();
            $table->string('font_heading', 30)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['color_primary', 'color_accent', 'color_button', 'color_background', 'font_body', 'font_heading']);
        });
    }
};
