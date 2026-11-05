<?php

namespace Tests\Feature;

use App\Models\Aventureiro;
use App\Models\Dungeon;
use App\Models\Guilda;
use App\Models\Quest;
use App\Services\ProcedureService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Random\Engine;
use Random\Randomizer;
use Tests\Support\FakeProcedures;
use Tests\TestCase;

class ExpedicoesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $this->app->instance(ProcedureService::class, new FakeProcedures);
        DB::statement("ATTACH DATABASE ':memory:' AS rpg_trabalho_bd");
        DB::statement('CREATE TABLE rpg_trabalho_bd.guildas (id INTEGER PRIMARY KEY, nome TEXT, nivel_minimo INTEGER, data_fundacao TEXT)');
        DB::statement("CREATE TABLE rpg_trabalho_bd.aventureiros (id INTEGER PRIMARY KEY, nickname TEXT UNIQUE, classe TEXT, nivel INTEGER DEFAULT 1, guilda_id INTEGER, senha TEXT, role TEXT NOT NULL DEFAULT 'jogador')");
        DB::statement('CREATE TABLE rpg_trabalho_bd.dungeons (id INTEGER PRIMARY KEY, nome TEXT, dificuldade TEXT, xp_recompensa INTEGER)');
        DB::statement('CREATE TABLE rpg_trabalho_bd.quests (id INTEGER PRIMARY KEY, aventureiro_id INTEGER, dungeon_id INTEGER, data_quest TEXT, sucesso BOOLEAN)');
        Guilda::create(['nome' => 'Bandeira', 'nivel_minimo' => 1]);
        Aventureiro::create(['nickname' => 'Jogador', 'classe' => 'Mago', 'nivel' => 1, 'guilda_id' => 1]);
        Aventureiro::create(['nickname' => 'Outro', 'classe' => 'Paladino', 'nivel' => 20, 'guilda_id' => 1]);
        Dungeon::create(['nome' => 'Destino escolhido', 'dificuldade' => 'Média', 'xp_recompensa' => 1500]);
        Dungeon::create(['nome' => 'Outro destino', 'dificuldade' => 'Fácil', 'xp_recompensa' => 500]);
    }

    private function sorteio(bool $sucesso): void
    {
        $this->app->instance(Randomizer::class, new Randomizer(new class($sucesso) implements Engine {
            public function __construct(private bool $sucesso) {}
            public function generate(): string { return str_repeat($this->sucesso ? "\xff" : "\x00", 8); }
        }));
    }

    private function dados(int $dungeon = 1): array
    {
        return ['dungeon_id' => $dungeon, 'submission_token' => (string) Str::uuid()];
    }

    public function test_visitante_nao_explora_dungeon(): void
    {
        $this->post('/expedicoes', $this->dados())->assertRedirect(route('login'));
        $this->postJson('/api/jogador/expedicoes', $this->dados())->assertUnauthorized();
        $this->assertSame(0, Quest::count());
    }

    public function test_sucesso_sorteado_registra_destino_data_e_xp_do_proprio_jogador(): void
    {
        $this->sorteio(true);
        $this->actingAs(Aventureiro::findOrFail(1))->post('/expedicoes', $this->dados())
            ->assertRedirect(route('guildas.dashboard', ['guilda' => 1]).'#expedicoes')->assertSessionHas('expedicao_resultado_id');
        $quest = Quest::firstOrFail();
        $this->assertTrue($quest->sucesso);
        $this->assertSame(1, $quest->aventureiro_id);
        $this->assertSame(1, $quest->dungeon_id);
        $this->assertSame(now()->toDateString(), $quest->data_quest);
        $this->assertSame(2, Aventureiro::findOrFail(1)->nivel);
        $this->assertSame(20, Aventureiro::findOrFail(2)->nivel);
        $this->get('/?guilda=1')->assertOk()->assertSee('VOCÊ RETORNOU VITORIOSO.')->assertSee('+1.500 XP')
            ->assertSee('EXPLORAR DUNGEON')->assertDontSee('O administrador registra o resultado');
    }

    public function test_falha_sorteada_registra_quest_sem_conceder_xp(): void
    {
        $this->sorteio(false);
        $this->actingAs(Aventureiro::findOrFail(1))->post('/expedicoes', $this->dados())->assertRedirect();
        $this->assertFalse(Quest::firstOrFail()->sucesso);
        $this->assertSame(1, Aventureiro::findOrFail(1)->nivel);
        $this->get('/?guilda=1')->assertOk()->assertSee('A DUNGEON VENCEU DESTA VEZ.')->assertSee('SEM XP NESTA TENTATIVA');
    }

    public function test_reenvio_nao_sorteia_de_novo_nem_duplica_xp_e_token_nao_troca_destino(): void
    {
        $this->sorteio(true);
        $this->actingAs(Aventureiro::findOrFail(1));
        $dados = $this->dados();
        $this->post('/expedicoes', $dados)->assertRedirect();
        $this->sorteio(false);
        $this->post('/expedicoes', $dados)->assertRedirect();
        $this->assertSame(1, Quest::count());
        $this->assertTrue(Quest::firstOrFail()->sucesso);
        $this->assertSame(2, Aventureiro::findOrFail(1)->nivel);
        $this->post('/expedicoes', array_replace($dados, ['dungeon_id' => 2]))->assertSessionHasErrors('submission_token');
        $this->assertSame(1, Quest::count());
    }

    public function test_cliente_nao_escolhe_resultado_data_conta_nivel_ou_recompensa(): void
    {
        $this->actingAs(Aventureiro::findOrFail(1));
        foreach (['sucesso' => true, 'aventureiro_id' => 2, 'data_quest' => '2026-01-01', 'nivel' => 99, 'xp_total' => 99999, 'xp_recompensa' => 99999, 'role' => 'admin'] as $campo => $valor) {
            $this->postJson('/expedicoes', $this->dados() + [$campo => $valor])->assertUnprocessable()->assertJsonValidationErrors($campo);
        }
        $this->assertSame(0, Quest::count());
        $this->assertSame(1, Aventureiro::findOrFail(1)->nivel);
    }

    public function test_aventureiro_sem_guilda_tambem_explora_e_retorna_ao_perfil(): void
    {
        $this->sorteio(true);
        Aventureiro::findOrFail(1)->update(['guilda_id' => null]);
        $this->actingAs(Aventureiro::findOrFail(1))->post('/expedicoes', $this->dados())
            ->assertRedirect(route('perfil.show', ['dungeon' => 1]).'#expedicoes');
        $this->assertSame(1, Quest::count());
    }

    public function test_api_sorteia_resultado_no_servidor_e_reenvio_retorna_mesma_quest(): void
    {
        $this->sorteio(false);
        Sanctum::actingAs(Aventureiro::findOrFail(1));
        $dados = $this->dados(2);
        $primeira = $this->postJson('/api/jogador/expedicoes', $dados)->assertCreated()
            ->assertJsonPath('data.aventureiro_id', 1)->assertJsonPath('data.dungeon_id', 2)->assertJsonPath('data.sucesso', false);
        $this->sorteio(true);
        $this->postJson('/api/jogador/expedicoes', $dados)->assertOk()
            ->assertJsonPath('data.id', $primeira->json('data.id'))->assertJsonPath('data.sucesso', false);
        $this->assertSame(1, Quest::count());
        $this->postJson('/api/jogador/expedicoes', $this->dados() + ['sucesso' => true])->assertUnprocessable();
    }

    public function test_destino_inexistente_ou_token_ausente_nao_cria_quest(): void
    {
        $this->actingAs(Aventureiro::findOrFail(1))->postJson('/expedicoes', ['dungeon_id' => 999])
            ->assertUnprocessable()->assertJsonValidationErrors(['dungeon_id', 'submission_token']);
        $this->assertSame(0, Quest::count());
    }

    public function test_resultado_de_outro_aventureiro_nao_vaza_no_feedback_da_conta(): void
    {
        $quest = Quest::create(['aventureiro_id' => 2, 'dungeon_id' => 1, 'data_quest' => now()->toDateString(), 'sucesso' => true]);
        $this->actingAs(Aventureiro::findOrFail(1))->withSession(['expedicao_resultado_id' => $quest->id])
            ->get('/?guilda=1')->assertOk()->assertViewHas('resultadoExpedicao', null)->assertDontSee('VOCÊ RETORNOU VITORIOSO.');
    }
}
