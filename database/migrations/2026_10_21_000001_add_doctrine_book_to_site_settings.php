<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Doctrinario del MMM (libro en FlipHTML5) para la página «Doctrina».
 * Vacío = el enlace oficial de config/bible.php.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('doctrine_book_url', 300)->nullable()->after('bethel_channel_id');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn('doctrine_book_url');
        });
    }
};
