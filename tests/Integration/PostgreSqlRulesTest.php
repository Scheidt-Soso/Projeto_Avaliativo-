<?php

namespace Tests\Integration;

use App\Models\Aventureiro;
use App\Models\Dungeon;
use App\Models\Guilda;
use App\Models\Quest;
use App\Services\AventureiroService;
use App\Services\DungeonService;
use App\Services\GuildaService;
use App\Services\ProcedureService;
use App\Services\QuestService;
use App\Support\DatabaseError;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class PostgreSqlRulesTest extends TestCase
{
    private Aventureiro $aventureiro;
    private Dungeon $dungeon;
    private Guilda $guilda;

    protected function setUp(): void
    {
        parent::setUp();
        if (getenv('COROA_TEST_LOCAL') !== '1' || !getenv('COROA_TEST_PASSWORD')) {
            throw new \RuntimeException('Configure COROA_TEST_LOCAL=1 e as credenciais do PostgreSQL local. Esta suíte não aceita o Neon.');
        }
        config(['database.default' => 'pgsql', 'database.connections.pgsql' => [
            'driver' => 'pgsql', 'host' => '127.0.0.1', 'port' => getenv('COROA_TEST_PORT') ?: 55432,
            'database' => getenv('COROA_TEST_DATABASE') ?: 'coroa_qa', 'username' => getenv('COROA_TEST_USER') ?: 'codex_qa',
            'password' => getenv('COROA_TEST_PASSWORD'), 'charset' => 'utf8', 'prefix' => '',
            'search_path' => 'rpg_trabalho_bd,public', 'sslmode' => 'disable',
        ]]);
        DB::purge('pgsql');
        DB::beginTransaction();
        $tag = Str::lower(Str::random(12));
        $this->guilda = Guilda::create(['nome' => 'Teste '.$tag, 'nivel_minimo' => 1]);
        $this->aventureiro = Aventureiro::create(['nickname' => 'Teste_'.$tag, 'classe' => 'Mago', 'nivel' => 1]);
        $this->dungeon = Dungeon::create(['nome' => 'Teste '.$tag, 'dificuldade' => 'Média', 'xp_recompensa' => 1500]);
    }

    protected function tearDown(): void
    {
        while (DB::transactionLevel() > 0) DB::rollBack();
        parent::tearDown();
    }

    public function test_procedure_registra_quest_e_atualiza_funcoes_nivel_e_ranking(): void
    {
        $procedures = app(ProcedureService::class);
        $falha = $procedures->registrarQuest($this->aventureiro->id, $this->dungeon->id, '2026-10-01', false);
        $this->assertSame(0, (int) $falha->xp_total);
        $this->assertSame(1, (int) $falha->nivel_atual);
        $sucesso = $procedures->registrarQuest($this->aventureiro->id, $this->dungeon->id, '2026-10-02', true);
        $this->assertSame(1500, (int) $sucesso->xp_total);
        $this->assertSame(2, (int) $sucesso->nivel_atual);
        $this->assertSame(2, $this->aventureiro->fresh()->nivel);
        $this->assertSame(1500, (int) $procedures->progresso($this->aventureiro->id)->xp_total);
        $this->assertSame($this->dungeon->id, Quest::findOrFail($sucesso->quest_id)->dungeon_id);
        $this->assertSame(1500, (int) DB::table('rpg_trabalho_bd.vw_matheusayres_ranking_xp')->where('nickname', $this->aventureiro->nickname)->value('xp_total_ganho'));
    }

    public function test_procedure_de_vinculo_respeita_nivel_e_atualiza_a_guilda(): void
    {
        $this->guilda->update(['nivel_minimo' => 10]);
        $this->rejeita(fn () => app(ProcedureService::class)->vincularAventureiroGuilda($this->aventureiro->id, $this->guilda->id), 'insuficiente');
        $this->assertNull($this->aventureiro->fresh()->guilda_id);
        $this->aventureiro->update(['nivel' => 10]);
        app(ProcedureService::class)->vincularAventureiroGuilda($this->aventureiro->id, $this->guilda->id);
        $this->assertSame($this->guilda->id, $this->aventureiro->fresh()->guilda_id);
    }

    public function test_trigger_de_guilda_impede_nivel_incompativel_com_membros(): void
    {
        app(ProcedureService::class)->vincularAventureiroGuilda($this->aventureiro->id, $this->guilda->id);
        $this->rejeita(fn () => app(GuildaService::class)->atualizar($this->guilda, ['nivel_minimo' => 10]), 'nível');
        $this->assertSame(1, $this->guilda->fresh()->nivel_minimo);
    }

    public function test_trigger_de_dungeon_bloqueia_xp_mas_permite_editar_nome(): void
    {
        app(QuestService::class)->criar(['aventureiro_id' => $this->aventureiro->id, 'dungeon_id' => $this->dungeon->id, 'data_quest' => '2026-10-01', 'sucesso' => true]);
        $this->rejeita(fn () => app(DungeonService::class)->atualizar($this->dungeon, ['xp_recompensa' => 9000]), 'recompensa');
        $this->assertSame(1500, $this->dungeon->fresh()->xp_recompensa);
        app(DungeonService::class)->atualizar($this->dungeon, ['nome' => 'Nome atualizado']);
        $this->assertSame('Nome atualizado', $this->dungeon->fresh()->nome);
    }

    public function test_editar_e_excluir_quest_recalcula_todos_os_envolvidos(): void
    {
        $outro = Aventureiro::create(['nickname' => 'Outro_'.Str::random(10), 'classe' => 'Mago', 'nivel' => 1]);
        $service = app(QuestService::class);
        $quest = $service->criar(['aventureiro_id' => $this->aventureiro->id, 'dungeon_id' => $this->dungeon->id, 'data_quest' => '2026-10-01', 'sucesso' => true]);
        $service->atualizar($quest, ['aventureiro_id' => $outro->id]);
        $this->assertSame(1, $this->aventureiro->fresh()->nivel);
        $this->assertSame(2, $outro->fresh()->nivel);
        $service->excluir($quest->fresh());
        $this->assertSame(1, $outro->fresh()->nivel);
        $this->assertFalse(DB::table('rpg_trabalho_bd.vw_matheusayres_ranking_xp')->where('nickname', $outro->nickname)->exists());
    }

    public function test_excluir_dungeon_remove_quests_e_recalcula_niveis(): void
    {
        app(QuestService::class)->criar(['aventureiro_id' => $this->aventureiro->id, 'dungeon_id' => $this->dungeon->id, 'data_quest' => '2026-10-01', 'sucesso' => true]);
        app(DungeonService::class)->excluir($this->dungeon);
        $this->assertSame(0, Quest::where('dungeon_id', $this->dungeon->id)->count());
        $this->assertSame(1, $this->aventureiro->fresh()->nivel);
    }

    public function test_criar_aventureiro_com_vinculo_invalido_faz_rollback_completo(): void
    {
        $this->guilda->update(['nivel_minimo' => 50]);
        $nickname = 'Rollback_'.Str::random(10);
        $this->rejeita(fn () => app(AventureiroService::class)->criar(['nickname' => $nickname, 'classe' => 'Mago', 'nivel' => 1, 'guilda_id' => $this->guilda->id]), 'insuficiente');
        $this->assertFalse(Aventureiro::where('nickname', $nickname)->exists());
    }

    private function rejeita(callable $operacao, string $trecho): void
    {
        try {
            DB::transaction($operacao);
            $this->fail('O banco deveria ter rejeitado a operação.');
        } catch (QueryException $exception) {
            $this->assertSame('P0001', $exception->errorInfo[0]);
            $this->assertStringContainsString(mb_strtolower($trecho), mb_strtolower(DatabaseError::message($exception)));
            $this->assertStringNotContainsString('CONTEXT', DatabaseError::message($exception));
        }
    }
}
