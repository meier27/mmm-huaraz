<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adelanto fijo por reserva (p. ej. S/ 5 en la convención) y captura del
 * pago que el cliente adjunta al reservar; el personal la acepta o rechaza.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('church_events', function (Blueprint $table) {
            $table->decimal('deposit_amount', 8, 2)->nullable()->after('deposit_min_percent');
        });

        Schema::table('reservations', function (Blueprint $table) {
            // Disco privado, nombre aleatorio; solo la ve el personal.
            $table->string('deposit_proof_path')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('deposit_proof_path');
        });

        Schema::table('church_events', function (Blueprint $table) {
            $table->dropColumn('deposit_amount');
        });
    }
};
