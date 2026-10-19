<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Color final de los degradados oscuros (tarjetas destacadas). El inicial es
 * el color principal. Vacío = el valor de resources/css/app.css.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->char('color_gradient_end', 7)->nullable()->after('color_background');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn('color_gradient_end');
        });
    }
};
