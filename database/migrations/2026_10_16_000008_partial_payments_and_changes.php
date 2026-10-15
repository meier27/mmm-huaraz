<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pagos parciales (adelanto + saldo), cambios de pedido y donación de
 * pedidos no recogidos.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Cada pago es un registro propio: un mismo pedido puede tener adelanto y saldo.
        Schema::create('reservation_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('method', 20);
            $table->string('reference', 100)->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->decimal('amount_paid', 10, 2)->default(0)->after('total');
            $table->timestamp('changed_at')->nullable()->after('expires_at');
            $table->foreignId('donated_by')->nullable()->after('delivered_at')->constrained('users')->nullOnDelete();
            $table->timestamp('donated_at')->nullable()->after('donated_by');
        });

        // Los datos de pago pasan a reservation_payments.
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropForeign(['payment_verified_by']);
        });
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'payment_reference', 'payment_verified_by', 'payment_verified_at']);
        });

        DB::table('reservations')->where('payment_status', 'verified')->update([
            'payment_status' => 'paid',
            'amount_paid' => DB::raw('total'),
        ]);

        Schema::table('event_settings', function (Blueprint $table) {
            // Porcentaje mínimo del primer pago; null = cualquier adelanto mayor que cero.
            $table->unsignedTinyInteger('deposit_min_percent')->nullable()->after('reservation_instructions');
        });
    }

    public function down(): void
    {
        Schema::table('event_settings', function (Blueprint $table) {
            $table->dropColumn('deposit_min_percent');
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->string('payment_method', 20)->nullable();
            $table->string('payment_reference', 100)->nullable();
            $table->foreignId('payment_verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('payment_verified_at')->nullable();
        });

        DB::table('reservations')->whereIn('payment_status', ['paid', 'partial'])->update(['payment_status' => 'verified']);

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropForeign(['donated_by']);
            $table->dropColumn(['amount_paid', 'changed_at', 'donated_by', 'donated_at']);
        });

        Schema::dropIfExists('reservation_payments');
    }
};
