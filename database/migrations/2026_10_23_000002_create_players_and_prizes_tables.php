<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Juegos con cuenta: jugadores (separados de los usuarios del panel), sus
 * partidas (el servidor arma cada nivel y guarda la solución), el avance por
 * nivel, y los premios por metas con su código de canje.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->string('nickname', 20)->unique();      // lo único público
            $table->string('name', 100);                   // privado: para entregar premios
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_public')->default(true);  // tarjeta de logros y ranking
            $table->boolean('is_active')->default(true);  // el equipo puede bloquear
            $table->timestamp('age_confirmed_at')->nullable();       // declaró tener 14 años o más
            $table->timestamp('terms_accepted_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('player_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('game_attempts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('player_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('session_id', 100)->nullable(); // visitantes sin cuenta
            $table->string('game', 30);
            $table->unsignedTinyInteger('level');
            $table->json('secret');                        // solución: nunca sale del servidor
            $table->json('state');                         // errores, pistas, respuestas…
            $table->unsignedTinyInteger('stars')->nullable();
            $table->unsignedInteger('seconds')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['player_id', 'game']);
            $table->index('created_at');
        });

        Schema::create('game_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->string('game', 30);
            $table->unsignedTinyInteger('level');
            $table->unsignedTinyInteger('stars');
            $table->unsignedInteger('best_seconds');
            $table->unsignedInteger('plays')->default(1);
            $table->timestamps();

            $table->unique(['player_id', 'game', 'level']);
        });

        Schema::create('prizes', function (Blueprint $table) {
            $table->id();
            $table->string('title', 120);
            $table->string('description', 500)->nullable();
            $table->string('goal_type', 20);               // stars | levels | game_levels
            $table->string('goal_game', 30)->nullable();
            $table->unsignedSmallInteger('goal_value');
            $table->unsignedSmallInteger('stock');
            $table->string('image_path')->nullable();
            $table->string('image_alt')->nullable();
            $table->boolean('is_published')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('prize_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prize_id')->constrained()->restrictOnDelete();
            $table->foreignId('player_id')->nullable()->constrained()->nullOnDelete();
            $table->string('player_nickname', 20);         // queda aunque se borre la cuenta
            $table->string('code', 12)->unique();
            $table->string('status', 20)->default('pending');
            $table->timestamp('delivered_at')->nullable();
            $table->foreignId('delivered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->unique(['prize_id', 'player_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prize_claims');
        Schema::dropIfExists('prizes');
        Schema::dropIfExists('game_progress');
        Schema::dropIfExists('game_attempts');
        Schema::dropIfExists('player_password_reset_tokens');
        Schema::dropIfExists('players');
    }
};
