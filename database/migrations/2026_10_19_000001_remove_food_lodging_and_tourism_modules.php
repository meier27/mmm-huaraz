<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * El sitio es solo de contenido cristiano: se retiran los módulos de comidas
 * (platos, ofertas, reservas y pagos), hospedaje y turismo.
 *
 * Irreversible. Antes de ejecutarla en un servidor con datos, respáldalos:
 *   mysqldump <bd> dishes dish_offerings reservations reservation_items
 *             reservation_payments accommodations tourist_places > respaldo.sql
 * Para volver atrás: restaurar ese respaldo y el código anterior (historial de git).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Hijas antes que padres (claves foráneas).
        foreach (['reservation_payments', 'reservation_items', 'reservations', 'dish_offerings', 'dishes', 'accommodations', 'tourist_places'] as $table) {
            Schema::dropIfExists($table);
        }

        $columns = array_values(array_filter(
            ['has_food', 'has_accommodations', 'has_tourism', 'reservations_open', 'reservations_title', 'reservation_instructions', 'deposit_min_percent', 'deposit_amount'],
            fn (string $column) => Schema::hasColumn('church_events', $column),
        ));

        if ($columns) {
            Schema::table('church_events', fn (Blueprint $table) => $table->dropColumn($columns));
        }

        // El rol «Entrega» solo servía para los platos: esas cuentas quedan como
        // editores desactivados hasta que un administrador decida qué hacer.
        DB::table('users')->where('role', 'delivery')->update(['role' => 'editor', 'is_active' => false]);
    }

    public function down(): void
    {
        throw new RuntimeException('Migración irreversible: restaura el respaldo de la base de datos y el código anterior.');
    }
};
