<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_settings', function (Blueprint $table) {
            // Las reservas permanecen cerradas hasta que un administrador las abra.
            $table->boolean('reservations_open')->default(false)->after('tiktok_url');
            $table->text('reservation_instructions')->nullable()->after('reservations_open');
        });

        Schema::create('dishes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->string('allergens', 255)->nullable();
            $table->string('image_path')->nullable();
            $table->string('image_alt')->nullable();
            $table->boolean('is_published')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_published', 'sort_order']);
        });

        // Un plato se ofrece en una fecha y horario concretos, con precio y stock propios.
        Schema::create('dish_offerings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dish_id')->constrained()->restrictOnDelete();
            $table->date('serve_date');
            $table->time('slot_start');
            $table->time('slot_end')->nullable();
            $table->decimal('price', 8, 2);
            $table->unsignedInteger('stock');
            $table->unsignedInteger('reserved')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['dish_id', 'serve_date', 'slot_start']);
            $table->index(['serve_date', 'is_active']);
        });

        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('code', 12)->unique();
            // Búsqueda por hash; copia cifrada (APP_KEY) para reenviar el enlace al cliente.
            $table->char('token_hash', 64)->unique();
            $table->text('token_encrypted');
            $table->uuid('idempotency_key')->unique();
            $table->string('customer_name', 120);
            $table->string('customer_phone', 30);
            $table->string('notes', 500)->nullable();
            $table->decimal('total', 10, 2);
            $table->string('status', 20)->index();
            $table->string('payment_status', 20)->index();
            $table->string('delivery_status', 20)->index();
            $table->string('payment_method', 20)->nullable();
            $table->string('payment_reference', 100)->nullable();
            $table->foreignId('payment_verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('payment_verified_at')->nullable();
            $table->foreignId('delivered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('delivered_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason', 255)->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();

            $table->index('customer_phone');
        });

        // Registros financieros: sin borrado en cascada.
        Schema::create('reservation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->restrictOnDelete();
            $table->foreignId('dish_offering_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('quantity');
            $table->decimal('unit_price', 8, 2);
            $table->decimal('line_total', 10, 2);
            $table->timestamps();

            $table->unique(['reservation_id', 'dish_offering_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_items');
        Schema::dropIfExists('reservations');
        Schema::dropIfExists('dish_offerings');
        Schema::dropIfExists('dishes');

        Schema::table('event_settings', function (Blueprint $table) {
            $table->dropColumn(['reservations_open', 'reservation_instructions']);
        });
    }
};
