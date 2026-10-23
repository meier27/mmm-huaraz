<?php

namespace Tests\Feature;

use App\Enums\ClaimStatus;
use App\Enums\PrizeGoal;
use App\Enums\Role;
use App\Livewire\Admin\PrizeClaimIndex;
use App\Livewire\Admin\WeeklyRankingManager;
use App\Models\DailyAnswer;
use App\Models\GameAttempt;
use App\Models\GamePoint;
use App\Models\GameProgress;
use App\Models\Player;
use App\Models\Prize;
use App\Models\PrizeClaim;
use App\Models\SiteSetting;
use App\Models\User;
use App\Models\WeeklyWinner;
use App\Services\GameRewards;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Fase 1 de dinámicas: puntos y ranking semanal con cierre y ganadores, reto
 * del día, racha y el requisito de seguir las páginas del Movimiento.
 */
class WeeklyDynamicsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        // Jueves 22 de octubre de 2026, 10:00 (hora de Lima).
        $this->travelTo(Carbon::parse('2026-10-22 10:00'));
        SiteSetting::current()->forceFill(['facebook_url' => 'https://www.facebook.com/mmmperu/', 'instagram_url' => 'https://www.instagram.com/peru_mmm/'])->save();
    }

    private function player(string $nickname = 'Ester'): Player
    {
        $player = new Player(['nickname' => $nickname, 'name' => 'Nombre de '.$nickname, 'email' => strtolower($nickname).'@example.test', 'password' => 'secreto123']);
        $player->forceFill(['age_confirmed_at' => now(), 'terms_accepted_at' => now()])->save();

        return $player;
    }

    private function points(Player $player, int $points, string $week = '2026-10-19'): void
    {
        GamePoint::create(['player_id' => $player->id, 'points' => $points, 'reason' => 'level', 'week' => $week]);
    }

    /** Juega «Ordena los libros» nivel 1 sin errores. */
    private function passBooksLevel(Player $player): TestResponse
    {
        $this->actingAs($player->fresh(), 'player')->get(route('games.play', ['ordena-los-libros', 1]));
        $attempt = GameAttempt::query()->latest()->first();
        foreach ($attempt->secret['sequence'] as $name) {
            $response = $this->postJson(route('games.move', $attempt), ['action' => 'pick', 'name' => $name]);
        }

        return $response;
    }

    public function test_new_stars_give_weekly_points_and_replaying_does_not(): void
    {
        $player = $this->player();

        $this->passBooksLevel($player)->assertJsonPath('finished.points', 30);
        $this->passBooksLevel($player)->assertJsonPath('finished.points', 0); // mismas estrellas: no suma

        $this->assertSame(30, app(GameRewards::class)->weeklyPointsOf($player));
        $this->assertSame('2026-10-19', GamePoint::first()->week->toDateString()); // lunes de la semana
    }

    public function test_daily_challenge_is_the_same_for_everyone_and_has_one_attempt(): void
    {
        $rewards = app(GameRewards::class);
        $challenge = $rewards->dailyChallenge();
        $this->assertSame($challenge, $rewards->dailyChallenge()); // mismo orden de opciones
        $this->assertContains($challenge['answer'], $challenge['choices']);

        $player = $this->player();
        $this->get(route('games.index'))->assertSee('Reto del día')->assertSee($challenge['prompt']);

        $this->actingAs($player, 'player')->post(route('games.daily'), ['choice' => 'inventada'])->assertStatus(422);
        $this->post(route('games.daily'), ['choice' => $challenge['answer']])->assertSessionHas('status', fn ($s) => str_starts_with($s, '¡Correcto!'));
        $this->post(route('games.daily'), ['choice' => $challenge['answer']])->assertSessionHas('status', 'Ya respondiste el reto de hoy. ¡Vuelve mañana!');

        $this->assertSame(1, DailyAnswer::count());
        $this->assertSame(GameRewards::DAILY_POINTS, $rewards->weeklyPointsOf($player));

        // Mañana hay otro reto (y la fecha decide cuál).
        $this->travel(1)->days();
        $this->assertNull($rewards->dailyAnswerOf($player));
    }

    public function test_streak_grows_on_consecutive_days_gives_bonus_and_resets_after_a_gap(): void
    {
        $rewards = app(GameRewards::class);
        $player = $this->player();

        foreach (range(1, 3) as $day) {
            $rewards->touchStreak($player);
            $rewards->touchStreak($player); // dos veces el mismo día cuenta una
            $this->travel(1)->days();
        }
        $player->refresh();
        $this->assertSame(3, $player->streak_days);
        $this->assertSame(GameRewards::STREAK_BONUS[3], (int) GamePoint::where('reason', 'streak')->sum('points'));

        $this->travel(2)->days(); // un día sin jugar
        $this->assertSame(0, GameRewards::currentStreak($player->fresh()));
        $rewards->touchStreak($player);
        $this->assertSame(1, $player->fresh()->streak_days);
    }

    public function test_weekly_ranking_page_shows_this_week_and_hides_private_profiles(): void
    {
        $this->points($a = $this->player('Ana'), 50);
        $this->points($this->player('Bea'), 80);
        $hidden = $this->player('Oculta');
        $hidden->update(['is_public' => false]);
        $this->points($hidden, 500);
        $this->points($this->player('Antigua'), 999, '2026-10-12'); // semana pasada

        $this->get(route('games.ranking'))->assertOk()
            ->assertSeeInOrder(['Bea', 'Ana'])->assertDontSee('Oculta')->assertDontSee('Antigua')
            ->assertSee('Cómo se suman puntos');
        $this->get(route('games.ranking', ['periodo' => 'siempre']))->assertOk()->assertDontSee('Cómo se suman puntos');
    }

    public function test_closing_a_week_creates_winners_with_codes_and_skips_recent_winners(): void
    {
        $prize = Prize::create(['title' => 'Biblia', 'goal_type' => PrizeGoal::Weekly, 'goal_value' => 1, 'stock' => 5, 'is_published' => true]);
        [$ana, $bea, $caleb, $dina] = [$this->player('Ana'), $this->player('Bea'), $this->player('Caleb'), $this->player('Dina')];
        foreach ([[$ana, 90], [$bea, 80], [$caleb, 70], [$dina, 60]] as [$player, $pts]) {
            $this->points($player, $pts, '2026-10-12');
        }
        // Ana ganó hace dos semanas: cede su lugar.
        WeeklyWinner::create(['week' => '2026-09-28', 'position' => 1, 'player_id' => $ana->id, 'player_nickname' => 'Ana', 'points' => 10]);

        $editor = User::factory()->role(Role::Editor)->create();
        $component = Livewire::actingAs($editor)->test(WeeklyRankingManager::class, ['week' => '2026-10-19'])
            ->assertSee('se podrá cerrar desde el lunes'); // la semana en curso no se cierra

        $component->set('week', '2026-10-12')->assertSee('Bea')
            ->set('prizes.1', $prize->id)->set('prizes.2', $prize->id)
            ->call('close')->assertHasNoErrors();

        $winners = WeeklyWinner::query()->whereDate('week', '2026-10-12')->orderBy('position')->get();
        $this->assertSame(['Bea', 'Caleb', 'Dina'], $winners->pluck('player_nickname')->all());
        $this->assertNotNull($winners[0]->prize_claim_id);
        $this->assertNull($winners[2]->prize_claim_id); // el 3.º, sin premio elegido
        $this->assertSame(2, PrizeClaim::where('prize_id', $prize->id)->count());

        // No se cierra dos veces; el premio aparece en «Mi avance» del ganador.
        $component->call('close')->assertHasErrors('prizes.0');
        $this->actingAs($bea, 'player')->get(route('games.account'))->assertSee('Mis premios ganados')->assertSee(PrizeClaim::find($winners[0]->prize_claim_id)->code);
        $this->get(route('games.ranking'))->assertSee('Ganadores de la semana')->assertSee('Caleb');
    }

    public function test_weekly_prizes_cannot_be_claimed_by_goal(): void
    {
        $prize = Prize::create(['title' => 'Biblia', 'goal_type' => PrizeGoal::Weekly, 'goal_value' => 1, 'stock' => 5, 'is_published' => true]);

        $this->actingAs($this->player(), 'player')->post(route('games.claim', $prize), ['follow' => 1])->assertForbidden();
    }

    public function test_claiming_requires_declaring_the_follow_and_delivery_requires_verifying_it(): void
    {
        $prize = Prize::create(['title' => 'Revista', 'goal_type' => PrizeGoal::Stars, 'goal_value' => 1, 'stock' => 3, 'is_published' => true]);
        $player = $this->player();
        $this->points($player, 10);
        GameProgress::create(['player_id' => $player->id, 'game' => 'trivia', 'level' => 1, 'stars' => 3, 'best_seconds' => 20]);

        $this->actingAs($player, 'player')->get(route('games.account'))->assertSee('Requisito: seguir al Movimiento')->assertSee('https://www.instagram.com/peru_mmm/');
        $this->post(route('games.claim', $prize))->assertSessionHasErrors('follow');
        $this->assertSame(0, PrizeClaim::count());

        $this->post(route('games.claim', $prize), ['follow' => 1])->assertRedirect();
        $claim = PrizeClaim::sole();
        $this->assertNotNull($claim->follow_declared_at);

        $editor = User::factory()->role(Role::Editor)->create();
        $admin = Livewire::actingAs($editor)->test(PrizeClaimIndex::class)
            ->assertSee('Declaró que sigue las páginas')
            ->call('deliver', $claim->id)->assertHasErrors("verified.{$claim->id}");
        $this->assertSame(ClaimStatus::Pending, $claim->fresh()->status);

        $admin->set("verified.{$claim->id}", true)->call('deliver', $claim->id)->assertHasNoErrors();
        $this->assertSame(ClaimStatus::Delivered, $claim->fresh()->status);
        $this->assertNotNull($claim->fresh()->follow_verified_at);
    }

    public function test_weekly_winners_confirm_the_follow_from_their_account(): void
    {
        $prize = Prize::create(['title' => 'Biblia', 'goal_type' => PrizeGoal::Weekly, 'goal_value' => 1, 'stock' => 5, 'is_published' => true]);
        $player = $this->player();
        $claim = PrizeClaim::create(['prize_id' => $prize->id, 'player_id' => $player->id, 'player_nickname' => 'Ester', 'code' => 'ABCD-EFGH', 'status' => ClaimStatus::Pending]);

        $this->actingAs($player, 'player')->post(route('games.follow', $claim))->assertSessionHasErrors('follow');
        $this->post(route('games.follow', $claim), ['follow' => 1])->assertRedirect();
        $this->assertNotNull($claim->fresh()->follow_declared_at);

        $this->actingAs($this->player('Otro'), 'player')->post(route('games.follow', $claim), ['follow' => 1])->assertNotFound();
    }
}
