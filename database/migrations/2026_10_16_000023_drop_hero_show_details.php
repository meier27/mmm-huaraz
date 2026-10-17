<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Las fechas y el lugar ya no se muestran sobre la imagen de portada (redundaban
 * con la barra de datos), así que su casilla deja de existir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('church_events', function (Blueprint $table) {
            $table->dropColumn('hero_show_details');
        });
    }

    public function down(): void
    {
        Schema::table('church_events', function (Blueprint $table) {
            $table->boolean('hero_show_details')->default(true)->after('hero_show_title');
        });
    }
};
