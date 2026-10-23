<?php

namespace Tests\Feature;

use App\Enums\GameItemType;
use App\Enums\Role;
use App\Livewire\Admin\GameItemManager;
use App\Models\AuditLog;
use App\Models\GameAttempt;
use App\Models\GameItem;
use App\Models\Motto;
use App\Models\Player;
use App\Models\User;
use App\Models\Verse;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\GameSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Juegos bíblicos: páginas, niveles armados y comprobados en el servidor, y
 * el contenido del panel (preguntas y palabras).
 */
class GamesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Verse::create(['reference' => 'Juan 3:16', 'text' => 'Porque de tal manera amó Dios al mundo, que ha dado á su Hijo unigénito', 'is_published' => true]);
    }

    public static function games(): array
    {
        return [
            'crucigrama' => ['crucigrama', 'crosswordGame('],
            'sopa' => ['sopa-de-letras', 'wordSearch('],
            'trivia' => ['trivia', 'triviaGame('],
            'versiculo' => ['completa-el-versiculo', 'verseGame('],
            'libros' => ['ordena-los-libros', 'booksGame('],
        ];
    }

    private function player(): Player
    {
        $player = new Player(['nickname' => 'Josue_7', 'name' => 'Josué', 'email' => 'josue@example.test', 'password' => 'secreto123']);
        $player->forceFill(['age_confirmed_at' => now(), 'terms_accepted_at' => now()])->save();

        return $player;
    }

    private function attempt(): GameAttempt
    {
        return GameAttempt::query()->latest()->firstOrFail();
    }

    private function move(GameAttempt $attempt, string $action, array $input = [])
    {
        return $this->postJson(route('games.move', $attempt), ['action' => $action, ...$input]);
    }

    #[DataProvider('games')]
    public function test_each_game_has_30_levels_and_its_first_level_is_built_by_the_server(string $slug, string $component): void
    {
        $this->get(route('games.levels', $slug))->assertOk()->assertSee('Nivel 30', false)->assertSee('Más juegos');

        $html = $this->get(route('games.play', [$slug, 1]))->assertOk()->assertSee($component, false)->getContent();
        $attempt = $this->attempt();
        $this->assertSame($slug, $attempt->game);
        // La solución se queda en el servidor.
        $this->assertStringNotContainsString('"secret"', $html);
        $this->assertStringNotContainsString('"letters":{', $html);
        $this->assertStringNotContainsString('"sequence"', $html);
        $this->assertStringNotContainsString('"answers"', $html);
    }

    public function test_index_and_home_list_the_games(): void
    {
        $this->get(route('games.index'))->assertOk()
            ->assertSee('Crucigrama bíblico')->assertSee('Rompecabezas')->assertSee('30 niveles', false)->assertSee('Crear cuenta');

        $this->get(route('home'))->assertOk()->assertSee('Aprende jugando')->assertSee(route('games.levels', 'trivia'));
    }

    public function test_guests_try_the_first_levels_and_players_unlock_them_in_order(): void
    {
        $this->get(route('games.play', ['trivia', 3]))->assertOk();
        $this->get(route('games.play', ['trivia', 4]))->assertRedirect(route('player.login'));

        $this->actingAs($this->player(), 'player');
        $this->get(route('games.play', ['trivia', 1]))->assertOk();
        $this->get(route('games.play', ['trivia', 2]))->assertRedirect(route('games.levels', 'trivia'));
        $this->get(route('games.play', ['trivia', 31]))->assertNotFound();
    }

    public function test_a_perfect_trivia_level_gives_three_stars_saves_progress_and_unlocks_the_next(): void
    {
        $player = $this->player();
        $this->actingAs($player, 'player')->get(route('games.play', ['trivia', 1]));
        $attempt = $this->attempt();
        $answers = $attempt->secret['answers'];
        $this->assertCount(5, $answers);

        // En orden: no se puede saltar preguntas.
        $this->move($attempt, 'answer', ['index' => 2, 'choice' => 'x'])->assertUnprocessable();

        foreach ($answers as $i => $answer) {
            $response = $this->move($attempt, 'answer', ['index' => $i, 'choice' => $answer['answer']])->assertOk()->assertJson(['correct' => true]);
        }

        $response->assertJsonPath('finished.stars', 3)->assertJsonPath('finished.saved', true)
            ->assertJsonPath('finished.next', route('games.play', ['trivia', 2]));
        $this->assertDatabaseHas('game_progress', ['player_id' => $player->id, 'game' => 'trivia', 'level' => 1, 'stars' => 3]);
        $this->assertSame(2, $player->unlockedLevel('trivia'));

        // Una partida terminada no admite más jugadas.
        $this->move($attempt, 'answer', ['index' => 5, 'choice' => 'x'])->assertStatus(409);
    }

    public function test_failing_a_level_saves_nothing_and_does_not_unlock(): void
    {
        $player = $this->player();
        $this->actingAs($player, 'player')->get(route('games.play', ['trivia', 1]));
        $attempt = $this->attempt();

        foreach (array_keys($attempt->secret['answers']) as $i) {
            $response = $this->move($attempt, 'answer', ['index' => $i, 'choice' => 'respuesta equivocada']);
        }

        $response->assertJsonPath('finished.stars', 0)->assertJsonPath('finished.next', null);
        $this->assertSame(0, $player->progress()->count());
    }

    public function test_attempts_belong_to_whoever_started_them(): void
    {
        $this->get(route('games.play', ['trivia', 1]));
        $attempt = $this->attempt();

        $this->actingAs($this->player(), 'player');
        $this->move($attempt, 'answer', ['index' => 0, 'choice' => 'x'])->assertForbidden();
    }

    public function test_books_are_checked_one_by_one_on_the_server(): void
    {
        $this->actingAs($this->player(), 'player');
        $this->get(route('games.play', ['ordena-los-libros', 1]));
        $attempt = $this->attempt();
        $sequence = $attempt->secret['sequence'];
        $this->assertCount(4, $sequence);

        $this->move($attempt, 'pick', ['name' => $sequence[1]])->assertJson(['correct' => false, 'mistakes' => 1]);
        $this->move($attempt, 'pick', ['name' => 'Libro inventado'])->assertUnprocessable();
        foreach ($sequence as $name) {
            $response = $this->move($attempt, 'pick', ['name' => $name])->assertJson(['correct' => true]);
        }
        $response->assertJsonPath('finished.stars', 2)->assertJsonPath('finished.saved', true);
    }

    public function test_a_verse_is_completed_with_the_missing_words(): void
    {
        $this->actingAs($this->player(), 'player');
        Verse::query()->where('reference', '!=', 'Juan 3:16')->delete();
        Motto::query()->update(['verse_text' => null]);
        $this->get(route('games.play', ['completa-el-versiculo', 1]));
        $attempt = $this->attempt();
        $words = $attempt->secret['verses'][0]['words'];

        $this->move($attempt, 'check', ['verse' => 0, 'words' => array_fill(0, count($words), 'nada')])->assertJson(['solved' => false]);
        $this->move($attempt, 'check', ['verse' => 0, 'words' => array_map('mb_strtolower', $words)])
            ->assertJson(['solved' => true, 'reference' => 'Juan 3:16'])
            ->assertJsonPath('finished.stars', 2); // 2 errores en el primer intento
    }

    public function test_word_search_selections_are_verified_against_the_grid(): void
    {
        $this->actingAs($this->player(), 'player');
        $this->get(route('games.play', ['sopa-de-letras', 1]));
        $attempt = $this->attempt();
        $targets = $attempt->secret['targets'];

        $this->move($attempt, 'select', ['cells' => [0, 2]])->assertUnprocessable(); // no son contiguas
        foreach ($targets as $target) {
            $response = $this->move($attempt, 'select', ['cells' => array_reverse($target['cells'])])->assertJson(['found' => true]);
        }
        $response->assertJsonPath('finished.stars', 3);
    }

    public function test_the_crossword_solution_never_leaves_the_server_and_hints_cost_stars(): void
    {
        $this->actingAs($this->player(), 'player');
        $this->get(route('games.play', ['crucigrama', 1]));
        $attempt = $this->attempt();
        $letters = $attempt->secret['letters'];
        $first = array_key_first($letters);

        $this->move($attempt, 'hint', ['cell' => $first])->assertJson(['letter' => $letters[$first], 'hints' => 1]);
        $this->move($attempt, 'submit', ['letters' => [$first => $letters[$first]]])->assertJson(['done' => false]);

        $wrong = $letters;
        $wrong[$first] = $letters[$first] === 'A' ? 'B' : 'A';
        $this->move($attempt, 'check', ['letters' => $wrong])->assertJson(['wrong' => [$first]]);

        $this->move($attempt, 'submit', ['letters' => array_map('mb_strtolower', $letters)])
            ->assertJson(['done' => true])->assertJsonPath('finished.stars', 2);
    }

    public function test_the_puzzle_is_replayed_on_the_server(): void
    {
        $this->actingAs($this->player(), 'player');
        Motto::query()->update(['image_path' => 'mottos/lema.jpg']);
        $this->get(route('games.play', ['rompecabezas', 1]))->assertOk()->assertSee('puzzleGame(', false);
        $attempt = $this->attempt();
        $tiles = $attempt->secret['tiles'];

        $this->move($attempt, 'submit', ['swaps' => [[0, 1]]])->assertJson(['solved' => false]);
        $this->move($attempt, 'submit', ['swaps' => [[0, 99]]])->assertUnprocessable();

        // Ordenar las piezas con el mínimo de cambios.
        $swaps = [];
        for ($i = 0; $i < count($tiles); $i++) {
            while ($tiles[$i] !== $i) {
                $j = array_search($i, $tiles, true);
                [$tiles[$i], $tiles[$j]] = [$tiles[$j], $tiles[$i]];
                $swaps[] = [$i, $j];
            }
        }
        $this->move($attempt, 'submit', ['swaps' => $swaps])->assertJson(['solved' => true])->assertJsonPath('finished.stars', 3);
    }

    public function test_examples_carry_their_bible_reference_and_are_not_reloaded_after_deletion(): void
    {
        $this->assertSame(0, GameItem::query()->whereNull('reference')->count());
        $this->assertGreaterThanOrEqual(10, GameItem::query()->ofType(GameItemType::Question)->count());

        GameItem::query()->where('answer', 'Jordán')->delete();
        $total = GameItem::count();
        $this->seed(GameSeeder::class);
        $this->assertSame($total, GameItem::count());
    }

    public function test_games_without_content_say_so_instead_of_breaking(): void
    {
        GameItem::query()->delete();

        $this->get(route('games.levels', 'trivia'))->assertOk()->assertSee('estará disponible');
        $this->get(route('games.play', ['trivia', 1]))->assertNotFound();
        $this->get(route('games.index'))->assertOk()->assertSee('Muy pronto');
        $this->get(route('games.play', ['ordena-los-libros', 1]))->assertOk(); // usa la propia Biblia
        $this->get('/juegos/no-existe')->assertNotFound();
    }

    public function test_grid_words_drop_accents_but_keep_the_enye(): void
    {
        $this->assertSame('JORDAN', GameItem::gridWord(' Jordán '));
        $this->assertSame('CANAAN', GameItem::gridWord('Canaán'));
        $this->assertSame('MONTAÑA', GameItem::gridWord('Mon taña'));
    }

    public function test_editor_manages_questions_and_words_with_validation_and_audit(): void
    {
        $editor = User::factory()->role(Role::Editor)->create();
        GameItem::query()->delete();

        $component = Livewire::actingAs($editor)->test(GameItemManager::class)
            ->call('create')
            ->set('form.prompt', '¿Quién fue el primer rey de Israel?')
            ->set('form.answer', 'Saúl')
            ->set('form.wrong_1', 'David')
            ->set('form.wrong_2', 'Salomón')
            ->set('form.reference', '')
            ->call('save')
            ->assertHasErrors(['form.wrong_3', 'form.reference']);

        $component->set('form.wrong_3', 'David')->set('form.reference', '1 Samuel 10:1')->call('save')
            ->assertHasErrors('form.wrong_3'); // repetida

        $component->set('form.wrong_3', 'Samuel')->call('save')->assertHasNoErrors();
        $question = GameItem::sole();
        $this->assertSame(['David', 'Salomón', 'Samuel'], $question->options);

        $component->call('edit', $question->id)->assertSet('form.wrong_2', 'Salomón')->call('cancel');

        $component->set('tab', 'word')->call('create')->assertSet('form.type', 'word')
            ->set('form.answer', 'Arca 2')->set('form.prompt', 'Pista')->set('form.reference', 'Génesis 7:1')
            ->call('save')->assertHasErrors('form.answer');
        $component->set('form.answer', 'Jo')->call('save')->assertHasErrors('form.answer');
        $component->set('form.answer', 'Getsemaní')->call('save')->assertHasNoErrors();

        $word = GameItem::query()->ofType(GameItemType::Word)->sole();
        $this->assertNull($word->options);
        $this->assertSame($editor->id, AuditLog::query()->where('auditable_id', $word->id)->where('auditable_type', $word->getMorphClass())->value('user_id'));
    }

    public function test_only_content_roles_reach_the_games_panel(): void
    {
        $this->actingAs(User::factory()->role(Role::Cashier)->create())->get(route('admin.games'))->assertForbidden();
        $this->actingAs(User::factory()->role(Role::Editor)->create())->get(route('admin.games'))->assertOk()->assertSee('Juegos bíblicos');
    }
}
