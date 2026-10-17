<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * - Cada texto de la portada del evento se muestra u oculta por separado
 *   (sustituye a «hero_show_text», que lo ocultaba todo junto).
 * - El formulario para registrar aportes pasa a ser opcional por evento
 *   (por defecto la página de donaciones solo muestra los métodos).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('church_events', function (Blueprint $table) {
            $table->boolean('hero_show_title')->default(true)->after('hero_button_url');
            $table->boolean('hero_show_details')->default(true)->after('hero_show_title');
            $table->boolean('hero_show_buttons')->default(true)->after('hero_show_details');
            $table->boolean('donation_reports_enabled')->default(false)->after('has_donations');
        });

        DB::table('church_events')->update([
            'hero_show_title' => DB::raw('hero_show_text'),
            'hero_show_details' => DB::raw('hero_show_text'),
            'hero_show_buttons' => DB::raw('hero_show_text'),
        ]);

        Schema::table('church_events', function (Blueprint $table) {
            $table->dropColumn('hero_show_text');
        });
    }

    public function down(): void
    {
        Schema::table('church_events', function (Blueprint $table) {
            $table->boolean('hero_show_text')->default(true)->after('hero_button_url');
        });

        DB::table('church_events')->update(['hero_show_text' => DB::raw('hero_show_title')]);

        Schema::table('church_events', function (Blueprint $table) {
            $table->dropColumn(['hero_show_title', 'hero_show_details', 'hero_show_buttons', 'donation_reports_enabled']);
        });
    }
};
