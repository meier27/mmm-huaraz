<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aportes declarados por los donantes y su verificación manual.
 * Lo declarado (declared_amount) y lo verificado (verified_amount) se
 * guardan por separado: una imagen nunca confirma un pago por sí sola.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 12)->unique();

            // El método puede despublicarse o borrarse; se conserva su nombre.
            $table->foreignId('donation_method_id')->nullable()->constrained()->nullOnDelete();
            $table->string('method_label', 150);

            $table->decimal('declared_amount', 10, 2);
            $table->string('operation_number', 50);
            $table->date('paid_on');

            $table->string('donor_name', 150)->nullable();
            $table->string('donor_phone', 30)->nullable();
            $table->string('donor_email')->nullable();
            $table->string('message', 1000)->nullable();

            // Disco privado (storage/app/private), nunca público.
            $table->string('receipt_path')->nullable();

            $table->string('status', 20)->default('pending');
            $table->decimal('verified_amount', 10, 2)->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_note', 500)->nullable();

            $table->dateTime('consent_at');
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['donation_method_id', 'operation_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};
