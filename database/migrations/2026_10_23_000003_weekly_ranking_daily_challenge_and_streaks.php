<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 1 de dinámicas: puntos semanales (ranking que se cierra cada semana
 * con ganadores), reto diario, racha de días y el requisito de seguir las
 * páginas del Movimiento (declarado al reclamar, verificado al entregar).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Libro de puntos: cada punto ganado queda registrado con su motivo.
        Schema::create('game_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('points');
            $table->string('reason', 20);                 // level | daily | streak
            $table->string('detail', 60)->nullable();     // p. ej. «trivia · nivel 4»
            $table->date('week');                          // lunes de la semana (hora de Lima)
            $table->timestamps();

            $table->index(['week', 'player_id']);
        });

        Schema::create('daily_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->date('day');
            $table->boolean('correct');
            $table->timestamps();

            $table->unique(['player_id', 'day']);
        });

        Schema::table('players', function (Blueprint $table) {
            $table->unsignedSmallInteger('streak_days')->default(0)->after('is_active');
            $table->date('last_active_on')->nullable()->after('streak_days');
        });

        Schema::create('weekly_winners', function (Blueprint $table) {
            $table->id();
            $table->date('week');
            $table->unsignedTinyInteger('position');
            $table->foreignId('player_id')->nullable()->constrained()->nullOnDelete();
            $table->string('player_nickname', 20);
            $table->unsignedInteger('points');
            $table->foreignId('prize_claim_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['week', 'position']);
        });

        Schema::table('prize_claims', function (Blueprint $table) {
            // Un premio del ranking semanal se puede ganar en varias semanas.
            // (primero el índice nuevo: la clave foránea necesita uno mientras se quita el único)
            $table->index(['prize_id', 'player_id']);
            $table->dropUnique(['prize_id', 'player_id']);
            $table->timestamp('follow_declared_at')->nullable()->after('status');
            $table->timestamp('follow_verified_at')->nullable()->after('follow_declared_at');
        });
    }

    public function down(): void
    {
        Schema::table('prize_claims', function (Blueprint $table) {
            $table->dropColumn(['follow_declared_at', 'follow_verified_at']);
            $table->dropIndex(['prize_id', 'player_id']);
            $table->unique(['prize_id', 'player_id']);
        });
        Schema::dropIfExists('weekly_winners');
        Schema::table('players', fn (Blueprint $table) => $table->dropColumn(['streak_days', 'last_active_on']));
        Schema::dropIfExists('daily_answers');
        Schema::dropIfExists('game_points');
    }
};
