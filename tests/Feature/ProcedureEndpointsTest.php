<?php

namespace Tests\Feature;

use App\Models\Aventureiro;
use App\Models\Dungeon;
use App\Models\Guilda;
use App\Services\ProcedureService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use PDOException;
use Tests\TestCase;

class ProcedureEndpointsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        DB::statement("ATTACH DATABASE ':memory:' AS rpg_trabalho_bd");
        DB::statement('CREATE TABLE rpg_trabalho_bd.guildas (id INTEGER PRIMARY KEY, nome TEXT, nivel_minimo INTEGER, data_fundacao TEXT)');
        DB::statement("CREATE TABLE rpg_trabalho_bd.aventureiros (id INTEGER PRIMARY KEY, nickname TEXT UNIQUE, classe TEXT, nivel INTEGER, guilda_id INTEGER, senha TEXT, role TEXT NOT NULL DEFAULT 'jogador')");
        DB::statement('CREATE TABLE rpg_trabalho_bd.dungeons (id INTEGER PRIMARY KEY, nome TEXT, dificuldade TEXT, xp_recompensa INTEGER)');
        DB::statement('CREATE TABLE rpg_trabalho_bd.quests (id INTEGER PRIMARY KEY, aventureiro_id INTEGER, dungeon_id INTEGER, data_quest TEXT, sucesso BOOLEAN)');

        Guilda::create(['id' => 1, 'nome' => 'Lâminas Rubras', 'nivel_minimo' => 1]);
        Aventureiro::create(['id' => 1, 'nickname' => 'Jogador', 'classe' => 'Guerreiro', 'nivel' => 5, 'role' => 'jogador']);
        Aventureiro::create(['id' => 2, 'nickname' => 'Outro', 'classe' => 'Mago', 'nivel' => 5, 'role' => 'jogador']);
        Aventureiro::create(['id' => 3, 'nickname' => 'Admin', 'classe' => 'Paladino', 'nivel' => 10, 'role' => 'admin']);
        Dungeon::create(['id' => 1, 'nome' => 'Cripta', 'dificuldade' => 'Média', 'xp_recompensa' => 40]);
    }

    public function test_jogador_registra_quest_pela_procedure_com_o_proprio_id(): void
    {
        $this->mock(ProcedureService::class, function ($mock) {
            $mock->shouldReceive('registrarQuest')
                ->once()
                ->with(1, 1, '2026-11-01', true)
                ->andReturn((object) ['xp_total' => 4000, 'nivel_atual' => 5]);
        });

        Sanctum::actingAs(Aventureiro::findOrFail(1));

        $this->postJson('/api/jogador/quests/registrar', [
            'aventureiro_id' => 2,
            'dungeon_id' => 1,
            'data_quest' => '2026-11-01',
            'sucesso' => true,
        ])->assertCreated()
            ->assertJsonPath('xp_total', 4000)
            ->assertJsonPath('nivel_atual', 5);
    }

    public function test_admin_precisa_informar_aventureiro_ao_registrar_quest(): void
    {
        $this->mock(ProcedureService::class, function ($mock) {
            $mock->shouldNotReceive('registrarQuest');
        });

        Sanctum::actingAs(Aventureiro::findOrFail(3));

        $this->postJson('/api/admin/quests/registrar', [
            'dungeon_id' => 1,
            'data_quest' => '2026-11-01',
            'sucesso' => true,
        ])->assertUnprocessable()->assertJsonValidationErrors('aventureiro_id');
    }

    public function test_jogador_nao_vincula_aventureiro_de_outra_pessoa(): void
    {
        $this->mock(ProcedureService::class, function ($mock) {
            $mock->shouldNotReceive('vincularAventureiroGuilda');
        });

        Sanctum::actingAs(Aventureiro::findOrFail(1));

        $this->postJson('/api/jogador/aventureiros/2/guilda', ['guilda_id' => 1])
            ->assertForbidden();
    }

    public function test_jogador_vincula_o_proprio_aventureiro(): void
    {
        $this->mock(ProcedureService::class, function ($mock) {
            $mock->shouldReceive('vincularAventureiroGuilda')->once()->with(1, 1);
        });

        Sanctum::actingAs(Aventureiro::findOrFail(1));

        $this->postJson('/api/jogador/aventureiros/1/guilda', ['guilda_id' => 1])
            ->assertOk()
            ->assertJsonPath('data.id', 1);
    }

    public function test_erro_da_procedure_retorna_422_com_a_mensagem_do_banco(): void
    {
        $pdo = new PDOException('Nivel insuficiente.');
        $pdo->errorInfo = ['P0001', 0, 'Nivel insuficiente.'];

        $this->mock(ProcedureService::class, function ($mock) use ($pdo) {
            $mock->shouldReceive('vincularAventureiroGuilda')
                ->once()
                ->andThrow(new QueryException('pgsql', 'CALL pr_vincular_aventureiro_guilda(?, ?)', [], $pdo));
        });

        Sanctum::actingAs(Aventureiro::findOrFail(1));

        $this->postJson('/api/jogador/aventureiros/1/guilda', ['guilda_id' => 1])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Nivel insuficiente.');
    }
}
