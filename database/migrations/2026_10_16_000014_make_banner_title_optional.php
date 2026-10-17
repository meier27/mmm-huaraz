<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * El título del banner pasa a ser opcional: hay banners que ya llevan el texto en la imagen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->string('title', 150)->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('banners')->whereNull('title')->update(['title' => '']);

        Schema::table('banners', function (Blueprint $table) {
            $table->string('title', 150)->nullable(false)->change();
        });
    }
};
