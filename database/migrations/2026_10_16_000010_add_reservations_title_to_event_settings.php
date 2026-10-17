<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nombre público de la venta de comidas (p. ej. «Actividad solidaria de los
 * hermanos de Huaraz»). Vacío = nombre predeterminado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_settings', function (Blueprint $table) {
            $table->string('reservations_title', 150)->nullable()->after('reservations_open');
        });
    }

    public function down(): void
    {
        Schema::table('event_settings', function (Blueprint $table) {
            $table->dropColumn('reservations_title');
        });
    }
};
