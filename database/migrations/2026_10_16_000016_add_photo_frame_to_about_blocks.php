<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marco tipo polaroid opcional: las fotos que ya traen su marco o son recortes
 * PNG transparentes se muestran tal cual.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('about_blocks', function (Blueprint $table) {
            $table->boolean('photo_frame')->default(true)->after('image_alt');
        });
    }

    public function down(): void
    {
        Schema::table('about_blocks', function (Blueprint $table) {
            $table->dropColumn('photo_frame');
        });
    }
};
