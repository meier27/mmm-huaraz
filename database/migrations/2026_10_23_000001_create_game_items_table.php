<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Preguntas y palabras de los juegos bíblicos, cada una con su cita.
 * - Pregunta: prompt = pregunta, answer = respuesta correcta, options = incorrectas.
 * - Palabra: answer = palabra, prompt = pista.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_items', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20);
            $table->string('prompt', 255);
            $table->string('answer', 120);
            $table->json('options')->nullable();
            $table->string('reference', 100)->nullable();
            $table->boolean('is_published')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['type', 'is_published']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_items');
    }
};
