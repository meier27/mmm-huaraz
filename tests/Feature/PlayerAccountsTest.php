<?php

namespace Tests\Feature;

use App\Enums\ClaimStatus;
use App\Enums\PrizeGoal;
use App\Enums\Role;
use App\Livewire\Admin\PlayerIndex;
use App\Livewire\Admin\PrizeClaimIndex;
use App\Livewire\Admin\PrizeManager;
use App\Models\AuditLog;
use App\Models\GameAttempt;
use App\Models\GameProgress;
use App\Models\Player;
use App\Models\Prize;
use App\Models\PrizeClaim;
use App\Models\User;
use App\Notifications\PlayerResetPassword;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Cuentas de jugadores (solo 14+), tarjeta de logros, ranking y premios con
 * código de canje.
 */
class PlayerAccountsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function player(string $nickname = 'Ester', array $attributes = []): Player
    {
        $player = new Player(['nickname' => $nickname, 'name' => 'Nombre de '.$nickname, 'email' => strtolower($nickname).'@example.test', 'password' => 'secreto123', ...$attributes]);
        $player->forceFill(['age_confirmed_at' => now(), 'terms_accepted_at' => now()])->save();

        return $player;
    }

    /** Niveles superados: [juego => [nivel => estrellas]] */
    private function progress(Player $player, string $game, int $levels, int $stars = 3): void
    {
        foreach (range(1, $levels) as $level) {
            GameProgress::create(['player_id' => $player->id, 'game' => $game, 'level' => $level, 'stars' => $stars, 'best_seconds' => 30]);
        }
    }

    private function prize(array $attributes = []): Prize
    {
        return Prize::create(['title' => 'Biblia', 'goal_type' => PrizeGoal::GameLevels, 'goal_game' => 'trivia', 'goal_value' => 3, 'stock' => 1, 'is_published' => true, ...$attributes]);
    }

    public function test_registration_requires_14_years_and_a_valid_unique_nickname(): void
    {
        $this->player('Ruth');
        $data = ['nickname' => 'Ruth', 'name' => 'Ana', 'email' => 'ana@example.test', 'password' => 'secreto123', 'password_confirmation' => 'secreto123', 'terms' => 1];

        $this->post(route('player.register'), $data)->assertSessionHasErrors(['nickname', 'age']);
        $this->post(route('player.register'), [...$data, 'nickname' => 'con espacios', 'age' => 1])->assertSessionHasErrors('nickname');

        $this->post(route('player.register'), [...$data, 'nickname' => 'Ana.17', 'email' => 'ANA@example.test', 'age' => 1])
            ->assertRedirect(route('games.account'));

        $player = Player::query()->where('nickname', 'Ana.17')->sole();
        $this->assertSame('ana@example.test', $player->email);
        $this->assertNotNull($player->age_confirmed_at);
        $this->assertAuthenticatedAs($player, 'player');
        $this->assertGuest('web'); // un jugador nunca es usuario del panel
    }

    public function test_players_log_in_and_out_and_blocked_players_cannot(): void
    {
        $player = $this->player();

        $this->post(route('player.login'), ['email' => 'ester@example.test', 'password' => 'mala'])->assertSessionHasErrors('email');
        $this->post(route('player.login'), ['email' => 'Ester@example.test', 'password' => 'secreto123'])->assertRedirect(route('games.account'));
        $this->assertAuthenticatedAs($player, 'player');

        $this->post(route('player.logout'))->assertRedirect(route('games.index'));
        $this->assertGuest('player');

        $player->forceFill(['is_active' => false])->save();
        $this->post(route('player.login'), ['email' => 'ester@example.test', 'password' => 'secreto123'])->assertSessionHasErrors('email');
    }

    public function test_players_have_no_access_to_the_admin_panel(): void
    {
        // Sesión de jugador (como en el navegador: el guard por defecto sigue siendo el del panel).
        Auth::guard('player')->login($this->player());

        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->get(route('admin.claims'))->assertRedirect(route('login'));
        $this->get(route('games.account'))->assertOk();
    }

    public function test_guests_are_sent_to_the_player_login(): void
    {
        $this->get(route('games.account'))->assertRedirect(route('player.login'));
    }

    public function test_password_reset_by_email(): void
    {
        Notification::fake();
        $player = $this->player();

        $this->post(route('player.password.email'), ['email' => 'nadie@example.test'])->assertSessionHas('status'); // misma respuesta
        $this->post(route('player.password.email'), ['email' => 'ester@example.test'])->assertSessionHas('status');

        Notification::assertSentTo($player, PlayerResetPassword::class, function ($notification) {
            $token = (fn () => $this->token)->call($notification);
            $this->post(route('player.password.update'), ['token' => $token, 'email' => 'ester@example.test', 'password' => 'nueva-clave-1', 'password_confirmation' => 'nueva-clave-1'])
                ->assertRedirect(route('player.login'));

            return true;
        });

        $this->post(route('player.login'), ['email' => 'ester@example.test', 'password' => 'nueva-clave-1'])->assertRedirect(route('games.account'));
    }

    public function test_stats_medals_profile_and_ranking(): void
    {
        $ester = $this->player('Ester');
        $this->progress($ester, 'trivia', 10, 3);
        $this->progress($ester, 'crucigrama', 2, 1);
        $hidden = $this->player('Oculto', ['is_public' => false]);
        $this->progress($hidden, 'trivia', 30);

        $stats = $ester->stats();
        $this->assertSame(32, $stats['stars']);
        $this->assertSame(12, $stats['levels']);
        $this->assertSame('bronce', $stats['games']['trivia']['medal']);
        $this->assertNull($stats['games']['crucigrama']['medal']);

        $this->get(route('games.profile', 'Ester'))->assertOk()->assertSee('32')->assertSee('Medalla de bronce')->assertDontSee('ester@example.test');
        $this->get(route('games.profile', 'Oculto'))->assertNotFound();

        $this->get(route('games.ranking', ['periodo' => 'siempre']))->assertOk()->assertSee('Ester')->assertDontSee('Oculto');
        $this->get(route('games.ranking', ['juego' => 'crucigrama']))->assertOk()->assertSee('Ester');
    }

    public function test_a_player_claims_a_reached_prize_once_and_stock_runs_out(): void
    {
        $prize = $this->prize();
        $ester = $this->player('Ester');
        $ruth = $this->player('Ruth');

        $this->actingAs($ester, 'player')->post(route('games.claim', $prize))->assertForbidden(); // aún no

        $this->progress($ester, 'trivia', 3);
        $this->progress($ruth, 'trivia', 3);

        $this->actingAs($ester->fresh(), 'player')->post(route('games.claim', $prize))->assertRedirect();
        $claim = PrizeClaim::sole();
        $this->assertMatchesRegularExpression('/^[A-Z2-9]{4}-[A-Z2-9]{4}$/', $claim->code);
        $this->get(route('games.account'))->assertSee($claim->code);

        $this->post(route('games.claim', $prize))->assertSessionHas('status', 'Ya reclamaste este premio.');
        $this->assertSame(1, PrizeClaim::count());

        $this->actingAs($ruth, 'player')->post(route('games.claim', $prize))->assertStatus(409); // agotado
    }

    public function test_finishing_a_level_announces_a_reached_prize(): void
    {
        $this->prize(['goal_type' => PrizeGoal::Stars, 'goal_game' => null, 'goal_value' => 3]);
        $ester = $this->player();

        $this->actingAs($ester, 'player')->get(route('games.play', ['ordena-los-libros', 1]));
        $attempt = GameAttempt::query()->latest()->first();
        foreach ($attempt->secret['sequence'] as $name) {
            $response = $this->postJson(route('games.move', $attempt), ['action' => 'pick', 'name' => $name]);
        }

        $response->assertJsonPath('finished.stars', 3)->assertJsonPath('finished.prizes', ['Biblia']);
    }

    public function test_the_team_delivers_or_cancels_claims_with_audit(): void
    {
        $prize = $this->prize(['stock' => 2]);
        $ester = $this->player();
        $claim = PrizeClaim::create(['prize_id' => $prize->id, 'player_id' => $ester->id, 'player_nickname' => 'Ester', 'code' => 'ABCD-EFGH', 'status' => ClaimStatus::Pending]);
        $editor = User::factory()->role(Role::Editor)->create();

        Livewire::actingAs($editor)->test(PrizeClaimIndex::class)
            ->set('search', 'abcd')->assertSee('ABCD-EFGH')->assertSee('Nombre de Ester')
            ->call('deliver', $claim->id);

        $claim->refresh();
        $this->assertSame(ClaimStatus::Delivered, $claim->status);
        $this->assertSame($editor->id, $claim->delivered_by);
        $this->assertTrue(AuditLog::query()->where('auditable_type', $claim->getMorphClass())->where('action', 'updated')->exists());

        // Con canjes no se borra el premio; anular devuelve la unidad.
        Livewire::actingAs($editor)->test(PrizeManager::class)->call('delete', $prize->id);
        $this->assertModelExists($prize);

        $other = PrizeClaim::create(['prize_id' => $prize->id, 'player_id' => null, 'player_nickname' => 'X', 'code' => 'JKMN-PQRS', 'status' => ClaimStatus::Pending]);
        $this->assertSame(0, $prize->remaining());
        Livewire::actingAs($editor)->test(PrizeClaimIndex::class)->call('cancel', $other->id);
        $this->assertSame(1, $prize->fresh()->remaining());

        $this->actingAs(User::factory()->role(Role::Cashier)->create())->get(route('admin.claims'))->assertForbidden();
    }

    public function test_prizes_are_created_with_a_reachable_goal(): void
    {
        Livewire::actingAs(User::factory()->role(Role::Editor)->create())->test(PrizeManager::class)
            ->call('create')
            ->set('form.title', 'Revista Impacto')
            ->set('form.goal_type', 'game_levels')
            ->set('form.goal_value', 31)
            ->call('save')
            ->assertHasErrors(['form.goal_game', 'form.goal_value'])
            ->set('form.goal_game', 'trivia')->set('form.goal_value', 30)->set('form.stock', 5)
            ->call('save')->assertHasNoErrors();

        $this->assertSame('Supera 30 niveles de «Trivia bíblica»', Prize::sole()->goalText());
    }

    public function test_the_team_blocks_a_player(): void
    {
        $player = $this->player('Apodo_feo');

        Livewire::actingAs(User::factory()->role(Role::Editor)->create())->test(PlayerIndex::class)
            ->set('search', 'feo')->assertSee('Apodo_feo')
            ->call('toggleActive', $player->id);

        $this->assertFalse($player->fresh()->is_active);
        $this->get(route('games.profile', 'Apodo_feo'))->assertNotFound();
    }

    public function test_a_player_hides_the_profile_and_deletes_the_account(): void
    {
        $player = $this->player();
        $this->progress($player, 'trivia', 3);
        $claim = PrizeClaim::create(['prize_id' => $this->prize()->id, 'player_id' => $player->id, 'player_nickname' => 'Ester', 'code' => 'ABCD-EFGH', 'status' => ClaimStatus::Delivered]);

        $this->actingAs($player, 'player')->put(route('games.account.update'), ['nickname' => 'Ester', 'name' => 'Ester', 'is_public' => 0])->assertRedirect();
        $this->get(route('games.profile', 'Ester'))->assertNotFound();

        $this->delete(route('games.account.destroy'), ['password' => 'mala'])->assertSessionHasErrors('password');
        $this->delete(route('games.account.destroy'), ['password' => 'secreto123'])->assertRedirect(route('games.index'));

        $this->assertModelMissing($player);
        $this->assertSame(0, GameProgress::count());
        $this->assertSame('Ester', $claim->fresh()->player_nickname); // constancia de la entrega
        $this->assertNull($claim->fresh()->player_id);
        $this->assertGuest('player');
    }
}
