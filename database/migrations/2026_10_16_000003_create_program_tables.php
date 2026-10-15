<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_days', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->string('title', 150)->nullable();
            $table->timestamps();
        });

        Schema::create('program_items', function (Blueprint $table) {
            $table->id();
            // Las actividades no tienen sentido sin su día: se eliminan con él.
            $table->foreignId('program_day_id')->constrained()->cascadeOnDelete();
            $table->foreignId('venue_id')->nullable()->constrained()->nullOnDelete();
            $table->time('starts_at');
            $table->time('ends_at')->nullable();
            $table->string('title', 150);
            $table->string('speaker', 150)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->index(['program_day_id', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_items');
        Schema::dropIfExists('program_days');
    }
};
