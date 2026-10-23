<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Índice del Doctrinario: [{page, title}], detectado del texto del PDF
 * (DoctrineIndexer) y editable en el panel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctrine_books', function (Blueprint $table) {
            $table->json('toc')->nullable()->after('page_height');
        });
    }

    public function down(): void
    {
        Schema::table('doctrine_books', function (Blueprint $table) {
            $table->dropColumn('toc');
        });
    }
};
