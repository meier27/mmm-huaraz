<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * La fecha de inicio del evento pasa a ser opcional («Fecha por confirmar»).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('church_events', function (Blueprint $table) {
            $table->date('starts_on')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('church_events')->whereNull('starts_on')->update(['starts_on' => DB::raw('COALESCE(ends_on, CURRENT_DATE)')]);

        Schema::table('church_events', function (Blueprint $table) {
            $table->date('starts_on')->nullable(false)->change();
        });
    }
};
