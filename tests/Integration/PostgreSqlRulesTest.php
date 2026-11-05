<?php

namespace Tests\Integration;

use App\Models\Aventureiro;
use App\Models\Dungeon;
use App\Models\Guilda;
use App\Models\Quest;
use App\Services\AventureiroService;
use App\Services\DungeonService;
use App\Services\GuildaService;
use App\Services\GuildDashboardService;
use App\Services\ProcedureService;
use App\Services\QuestService;
use App\Support\DatabaseError;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Random\Engine;
use Random\Randomizer;
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

    public function test_login_admin_web_abre_paineis_e_registra_quest_com_o_banco_real(): void
    {
        $this->aventureiro->update(['role' => 'admin', 'senha' => Hash::make('senha-admin-de-teste')]);
        $this->post('/login', ['nickname' => $this->aventureiro->nickname, 'senha' => 'incorreta'])->assertSessionHasErrors('nickname');
        $this->assertGuest();
        $this->post('/login', ['nickname' => $this->aventureiro->nickname, 'senha' => 'senha-admin-de-teste'])
            ->assertRedirect(route('guildas.dashboard'));
        $this->assertAuthenticatedAs($this->aventureiro);
        $this->get('/admin/dashboard')->assertRedirect('/');
        $this->get('/')->assertOk();
        $this->get('/perfil')->assertOk()->assertSee('REGISTRAR RESULTADO');
        foreach (['guildas', 'aventureiros', 'dungeons', 'quests'] as $recurso) {
            $this->get('/cadastros/'.$recurso)->assertOk();
            $this->get('/cadastros/'.$recurso.'/create')->assertOk();
        }
        foreach (['ranking', 'vinculos', 'inativos', 'dungeons', 'sucessos'] as $relatorio) {
            $this->get('/relatorios?aba='.$relatorio)->assertOk()->assertViewHas('aba', $relatorio);
        }
        $this->post('/cadastros/quests', [
            'submission_token' => (string) Str::uuid(), 'aventureiro_id' => $this->aventureiro->id,
            'dungeon_id' => $this->dungeon->id, 'data_quest' => '2026-11-01', 'sucesso' => true,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(2, $this->aventureiro->fresh()->nivel);
        $this->assertSame(1, $this->aventureiro->quests()->count());
        $this->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
        $this->get('/cadastros/quests/create')->assertRedirect(route('login'));
    }

    public function test_login_admin_api_emite_token_registra_resultados_e_revoga_acesso(): void
    {
        $this->aventureiro->update(['role' => 'admin', 'senha' => Hash::make('senha-admin-de-teste')]);
        $this->postJson('/api/login', ['nickname' => $this->aventureiro->nickname, 'senha' => 'incorreta'])->assertUnauthorized();
        $login = $this->postJson('/api/login', ['nickname' => $this->aventureiro->nickname, 'senha' => 'senha-admin-de-teste'])
            ->assertOk()->assertJsonPath('aventureiro.role', 'admin')->assertJsonMissingPath('aventureiro.senha');
        $this->withToken($login->json('token'))->getJson('/api/admin/quests')->assertOk();
        $dados = ['aventureiro_id' => $this->aventureiro->id, 'dungeon_id' => $this->dungeon->id, 'data_quest' => '2026-11-01'];
        $this->postJson('/api/admin/quests', $dados + ['sucesso' => false])->assertCreated()->assertJsonPath('data.sucesso', false);
        $this->assertSame(1, $this->aventureiro->fresh()->nivel);
        $this->postJson('/api/admin/quests/registrar', $dados + ['sucesso' => true])
            ->assertCreated()->assertJsonPath('xp_total', 1500)->assertJsonPath('nivel_atual', 2);
        $this->assertSame(2, $this->aventureiro->quests()->count());
        $this->postJson('/api/logout')->assertOk();
        $this->assertSame(0, $this->aventureiro->tokens()->count());
        Auth::forgetGuards();
        $this->getJson('/api/admin/quests')->assertUnauthorized();
    }

    public function test_jogador_nao_consegue_gravar_resultado_no_postgresql(): void
    {
        $this->actingAs($this->aventureiro);
        $dados = ['aventureiro_id' => $this->aventureiro->id, 'dungeon_id' => $this->dungeon->id,
            'data_quest' => '2026-11-01', 'submission_token' => (string) Str::uuid()];
        foreach ([true, false] as $sucesso) {
            foreach (['/perfil/quests', '/cadastros/quests', '/api/jogador/quests', '/api/jogador/quests/registrar', '/api/admin/quests', '/api/admin/quests/registrar'] as $rota) {
                $this->postJson($rota, $dados + ['sucesso' => $sucesso])->assertForbidden();
            }
        }
        $this->assertSame(0, $this->aventureiro->quests()->count());
        $this->assertSame(1, $this->aventureiro->fresh()->nivel);
        $this->assertSame(0, (int) app(ProcedureService::class)->progresso($this->aventureiro->id)->xp_total);
    }

    public function test_atlas_carrega_cinco_resultados_reais_por_dungeon_no_postgresql(): void
    {
        app(ProcedureService::class)->vincularAventureiroGuilda($this->aventureiro->id, $this->guilda->id);
        $outra = Dungeon::create(['nome' => 'Outra '.Str::random(10), 'dificuldade' => 'Fácil', 'xp_recompensa' => 500]);
        foreach ([$this->dungeon->id, $outra->id] as $dungeonId) {
            foreach (range(1, 7) as $dia) {
                Quest::create(['aventureiro_id' => $this->aventureiro->id, 'dungeon_id' => $dungeonId,
                    'data_quest' => '2026-10-'.str_pad($dia, 2, '0', STR_PAD_LEFT), 'sucesso' => $dia % 2 === 0]);
            }
        }
        $dungeons = app(GuildDashboardService::class)->dashboard($this->guilda->id)['dungeons']->keyBy('id');
        foreach ([$this->dungeon->id, $outra->id] as $dungeonId) {
            $this->assertSame(3, $dungeons[$dungeonId]->guild_successes_count);
            $this->assertSame(4, $dungeons[$dungeonId]->guild_failures_count);
            $this->assertCount(5, $dungeons[$dungeonId]->quests);
            $this->assertFalse($dungeons[$dungeonId]->quests->first()->sucesso);
            $this->assertSame($this->aventureiro->nickname, $dungeons[$dungeonId]->quests->first()->aventureiro->nickname);
        }
    }

    public function test_expedicao_sorteada_usa_procedure_real_e_nao_sorteia_ao_reenviar(): void
    {
        $this->actingAs($this->aventureiro);
        $this->app->instance(Randomizer::class, new Randomizer(new class implements Engine {
            public function generate(): string { return str_repeat("\xff", 8); }
        }));
        $dados = ['dungeon_id' => $this->dungeon->id, 'submission_token' => (string) Str::uuid()];
        $this->post('/expedicoes', $dados)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(2, $this->aventureiro->fresh()->nivel);
        $this->assertTrue($this->aventureiro->quests()->firstOrFail()->sucesso);
        $this->assertSame(DB::selectOne('SELECT CURRENT_DATE AS data')->data, $this->aventureiro->quests()->firstOrFail()->data_quest);
        $this->post('/expedicoes', $dados)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(1, $this->aventureiro->quests()->count());
        $this->assertSame(1500, (int) app(ProcedureService::class)->progresso($this->aventureiro->id)->xp_total);
        $this->app->instance(Randomizer::class, new Randomizer(new class implements Engine {
            public function generate(): string { return str_repeat("\x00", 8); }
        }));
        $this->post('/expedicoes', ['dungeon_id' => $this->dungeon->id, 'submission_token' => (string) Str::uuid()])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(2, $this->aventureiro->quests()->count());
        $this->assertSame(1, $this->aventureiro->quests()->where('sucesso', false)->count());
        $this->assertSame(1500, (int) app(ProcedureService::class)->progresso($this->aventureiro->id)->xp_total);
        $this->assertSame(2, $this->aventureiro->fresh()->nivel);
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
