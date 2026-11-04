<?php

namespace Tests\Feature;

use App\Models\Aventureiro;
use App\Models\Dungeon;
use App\Models\Guilda;
use App\Models\Quest;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GuildDashboardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $this->app->instance(\App\Services\ProcedureService::class, new \Tests\Support\FakeProcedures);
        DB::statement("ATTACH DATABASE ':memory:' AS rpg_trabalho_bd");
        DB::statement('CREATE TABLE rpg_trabalho_bd.guildas (id INTEGER PRIMARY KEY, nome TEXT, nivel_minimo INTEGER, data_fundacao TEXT)');
        DB::statement("CREATE TABLE rpg_trabalho_bd.aventureiros (id INTEGER PRIMARY KEY, nickname TEXT UNIQUE, classe TEXT, nivel INTEGER, guilda_id INTEGER, senha TEXT, role TEXT NOT NULL DEFAULT 'jogador')");
        DB::statement('CREATE TABLE rpg_trabalho_bd.dungeons (id INTEGER PRIMARY KEY, nome TEXT, dificuldade TEXT, xp_recompensa INTEGER)');
        DB::statement('CREATE TABLE rpg_trabalho_bd.quests (id INTEGER PRIMARY KEY, aventureiro_id INTEGER, dungeon_id INTEGER, data_quest TEXT, sucesso BOOLEAN)');
        Guilda::create(['nome' => 'Lâminas Rubras', 'nivel_minimo' => 5, 'data_fundacao' => '2020-01-15']);
        Guilda::create(['nome' => 'Corvos de Ônix', 'nivel_minimo' => 10]);
        Aventureiro::create(['nickname' => 'Kaelthas', 'classe' => 'Guerreiro', 'nivel' => 12, 'guilda_id' => 1]);
        Aventureiro::create(['nickname' => 'Elara', 'classe' => 'Clérigo', 'nivel' => 15, 'guilda_id' => 1]);
        Aventureiro::create(['nickname' => 'Lyriel', 'classe' => 'Mago', 'nivel' => 25, 'guilda_id' => 2]);
        Dungeon::create(['nome' => 'Cripta do Primeiro Rei', 'dificuldade' => 'Média', 'xp_recompensa' => 40]);
        Dungeon::create(['nome' => 'Torre Esquecida', 'dificuldade' => 'Difícil', 'xp_recompensa' => 100]);
        Quest::create(['aventureiro_id' => 1, 'dungeon_id' => 1, 'data_quest' => '2026-10-01', 'sucesso' => true]);
        Quest::create(['aventureiro_id' => 2, 'dungeon_id' => 2, 'data_quest' => '2026-10-02', 'sucesso' => false]);
        Quest::create(['aventureiro_id' => 3, 'dungeon_id' => 2, 'data_quest' => '2026-10-03', 'sucesso' => true]);
        $this->actingAs(Aventureiro::findOrFail(1));
    }

    public function test_painel_mostra_membros_e_estatisticas_apenas_da_guilda_escolhida(): void
    {
        $this->get('/?guilda=1')->assertOk()
            ->assertViewHas('guilda', fn ($guilda) => $guilda->id === 1)
            ->assertViewHas('resumo', ['membros' => 2, 'quests' => 2, 'sucessos' => 1, 'xp' => 40])
            ->assertViewHas('historico', fn ($historico) => $historico->count() === 2 && $historico->every(fn ($quest) => $quest->aventureiro->guilda_id === 1))
            ->assertSee('Kaelthas')->assertSee('Elara')->assertDontSee('Lyriel');
        $this->get('/?guilda=1')->assertSee('Conta de Kaelthas')->assertSee(route('logout'))->assertSee('SAIR DO QUARTEL');
    }

    public function test_painel_usa_primeira_guilda_quando_nao_ha_selecao(): void
    {
        $this->get('/')->assertOk()->assertViewHas('guilda', fn ($guilda) => $guilda->id === 1);
    }

    public function test_cada_guilda_exibe_sua_identidade_de_quartel(): void
    {
        $this->get('/?guilda=1')->assertOk()->assertSee('Bastião das Lâminas')->assertSee('data-biome="ashlands"', false);
        $this->get('/?guilda=2')->assertOk()->assertSee('Vigília de Ônix')->assertSee('data-biome="mistlands"', false)
            ->assertDontSee('Bastião das Lâminas');
    }

    public function test_guilda_nova_recebe_base_padrao_sem_modificar_seus_dados(): void
    {
        $guilda = Guilda::create(['nome' => 'Nova Bandeira', 'nivel_minimo' => 1]);
        $this->get('/?guilda='.$guilda->id)->assertOk()->assertSee('Refúgio dos Viajantes');
        $this->assertSame('Nova Bandeira', $guilda->fresh()->nome);
    }

    public function test_guilda_sem_membros_tem_resumo_vazio(): void
    {
        $guilda = Guilda::create(['nome' => 'Bandeira Solitária', 'nivel_minimo' => 1]);
        $this->get('/?guilda='.$guilda->id)->assertOk()
            ->assertViewHas('resumo', ['membros' => 0, 'quests' => 0, 'sucessos' => 0, 'xp' => 0]);
    }

    public function test_atlas_conta_somente_tentativas_e_vitorias_da_guilda_atual(): void
    {
        $this->get('/?guilda=1')->assertOk()->assertViewHas('dungeons', function ($dungeons) {
            $cripta = $dungeons->firstWhere('id', 1);
            $torre = $dungeons->firstWhere('id', 2);

            return $cripta->guild_attempts_count === 1 && $cripta->guild_successes_count === 1
                && $torre->guild_attempts_count === 1 && $torre->guild_successes_count === 0;
        });
        $this->get('/?guilda=2')->assertOk()->assertViewHas('dungeons', fn ($dungeons) =>
            $dungeons->firstWhere('id', 1)->guild_attempts_count === 0
            && $dungeons->firstWhere('id', 2)->guild_successes_count === 1);
    }

    public function test_atlas_sem_dungeons_mostra_estado_vazio(): void
    {
        Quest::query()->delete();
        Dungeon::query()->delete();
        $this->get('/')->assertOk()->assertSee('O atlas está em branco.')->assertViewHas('dungeons', fn ($dungeons) => $dungeons->isEmpty());
    }

    public function test_selecao_preserva_exatamente_a_dungeon_escolhida_sem_mudar_nivel_ou_criar_quest(): void
    {
        $antes = Aventureiro::findOrFail(1)->getAttributes();
        $response = $this->post('/guildas/1/expedicao/selecionar', ['aventureiro_id' => 1, 'dungeon_id' => 2]);
        $response->assertRedirect(route('guildas.dashboard', ['guilda' => 1]))
            ->assertSessionHas('expedicao.selecao', ['guilda_id' => 1, 'aventureiro_id' => 1, 'dungeon_id' => 2]);
        $this->get('/?guilda=1')->assertOk()->assertViewHas('destino', fn ($destino) => $destino['dungeon']->id === 2 && $destino['aventureiro']->id === 1)
            ->assertDontSee('SORTEAR DUNGEON')->assertDontSee('EXPEDIÇÃO ALEATÓRIA');
        $this->assertSame(3, Quest::count());
        $this->assertSame($antes, Aventureiro::findOrFail(1)->getAttributes());
    }

    public function test_aventureiro_de_outra_guilda_nao_pode_ser_enviado(): void
    {
        $this->from('/?guilda=1')->post('/guildas/1/expedicao/selecionar', ['aventureiro_id' => 3, 'dungeon_id' => 1])
            ->assertSessionHasErrors('aventureiro_id')->assertSessionMissing('expedicao.selecao');
    }

    public function test_jogador_nao_prepara_expedicao_para_outro_membro_da_mesma_guilda(): void
    {
        $this->post('/guildas/1/expedicao/selecionar', ['aventureiro_id' => 2, 'dungeon_id' => 1])
            ->assertForbidden()->assertSessionMissing('expedicao.selecao');
    }

    public function test_destino_de_outra_guilda_nao_aparece_no_painel(): void
    {
        $this->withSession(['expedicao.selecao' => ['guilda_id' => 2, 'aventureiro_id' => 3, 'dungeon_id' => 2]])
            ->get('/?guilda=1')->assertOk()->assertViewHas('destino', null);
    }

    public function test_destino_removido_ou_ausente_e_rejeitado_sem_substituicao_automatica(): void
    {
        $this->from('/?guilda=1')->post('/guildas/1/expedicao/selecionar', ['aventureiro_id' => 1, 'dungeon_id' => 999])
            ->assertSessionHasErrors('dungeon_id')->assertSessionMissing('expedicao.selecao');
        $this->post('/guildas/1/expedicao/selecionar', ['aventureiro_id' => 1])
            ->assertSessionHasErrors('dungeon_id')->assertSessionMissing('expedicao.selecao');
        $this->assertSame(3, Quest::count());
    }

    public function test_selecao_reconsulta_dados_atuais_e_descarta_membro_transferido(): void
    {
        $this->withSession(['expedicao.selecao' => ['guilda_id' => 1, 'aventureiro_id' => 1, 'dungeon_id' => 2]]);
        Dungeon::findOrFail(2)->update(['nome' => 'Torre Renomeada']);
        $this->get('/?guilda=1')->assertViewHas('destino', fn ($destino) => $destino['dungeon']->nome === 'Torre Renomeada');
        Aventureiro::findOrFail(1)->update(['guilda_id' => 2]);
        $this->get('/?guilda=1')->assertViewHas('destino', null)->assertSessionMissing('expedicao.selecao');
    }

    public function test_rota_antiga_de_sorteio_foi_removida(): void
    {
        $this->post('/guildas/1/expedicao/sortear', ['aventureiro_id' => 1])->assertNotFound();
    }

    public function test_api_de_quests_usa_os_nomes_atuais_do_banco(): void
    {
        $admin = Aventureiro::findOrFail(1);
        $admin->update(['role' => 'admin']);
        $this->actingAs($admin);
        $this->postJson('/api/quests', ['aventureiro_id' => 1, 'dungeon_id' => 2, 'data_quest' => '2026-10-01', 'sucesso' => true])
            ->assertCreated()->assertJsonPath('data.dungeon_id', 2)->assertJsonPath('data.data_quest', '2026-10-01')
            ->assertJsonPath('data.dungeon.nome', 'Torre Esquecida');
        $this->assertSame(4, Quest::count());
        $this->assertSame(3, Dungeon::findOrFail(2)->quests()->count());
    }
}
