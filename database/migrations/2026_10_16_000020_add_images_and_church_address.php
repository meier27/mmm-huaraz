<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Foto de cada sede de evento y dirección (con mapa) del templo de Huaraz.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('maps_url');
            $table->string('image_alt')->nullable()->after('image_path');
        });

        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('address', 255)->nullable()->after('service_schedule');
            $table->decimal('latitude', 10, 7)->nullable()->after('address');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });
    }

    public function down(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->dropColumn(['image_path', 'image_alt']);
        });

        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['address', 'latitude', 'longitude']);
        });
    }
};
