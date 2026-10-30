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
        DB::statement("ATTACH DATABASE ':memory:' AS rpg_trabalho_bd");
        DB::statement('CREATE TABLE rpg_trabalho_bd.guildas (id INTEGER PRIMARY KEY, nome TEXT, nivel_minimo INTEGER, data_fundacao TEXT)');
        DB::statement('CREATE TABLE rpg_trabalho_bd.aventureiros (id INTEGER PRIMARY KEY, nickname TEXT UNIQUE, classe TEXT, nivel INTEGER, guilda_id INTEGER)');
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
    }

    public function test_painel_mostra_membros_e_estatisticas_apenas_da_guilda_escolhida(): void
    {
        $this->get('/?guilda=1')->assertOk()
            ->assertViewHas('guilda', fn ($guilda) => $guilda->id === 1)
            ->assertViewHas('resumo', ['membros' => 2, 'quests' => 2, 'sucessos' => 1, 'xp' => 40])
            ->assertViewHas('historico', fn ($historico) => $historico->count() === 2 && $historico->every(fn ($quest) => $quest->aventureiro->guilda_id === 1))
            ->assertSee('Kaelthas')->assertSee('Elara')->assertDontSee('Lyriel');
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

    public function test_sorteio_usa_dungeon_real_sem_mudar_nivel_ou_criar_quest(): void
    {
        $antes = Aventureiro::findOrFail(1)->getAttributes();
        $response = $this->post('/guildas/1/expedicao/sortear', ['aventureiro_id' => 1]);
        $response->assertRedirect(route('guildas.dashboard', ['guilda' => 1]))
            ->assertSessionHas('expedicao.oferta', fn ($oferta) => $oferta['guilda_id'] === 1 && $oferta['aventureiro_id'] === 1 && Dungeon::whereKey($oferta['dungeon']['id'])->exists());
        $this->assertSame(3, Quest::count());
        $this->assertSame($antes, Aventureiro::findOrFail(1)->getAttributes());
    }

    public function test_aventureiro_de_outra_guilda_nao_pode_ser_enviado(): void
    {
        $this->from('/?guilda=1')->post('/guildas/1/expedicao/sortear', ['aventureiro_id' => 3])
            ->assertSessionHasErrors('aventureiro_id')->assertSessionMissing('expedicao.oferta');
    }

    public function test_oferta_de_outra_guilda_nao_aparece_no_painel(): void
    {
        $this->withSession(['expedicao.oferta' => ['guilda_id' => 2, 'aventureiro_id' => 3, 'dungeon' => ['nome' => 'Oferta de outra guilda']]])
            ->get('/?guilda=1')->assertOk()->assertViewHas('oferta', null)->assertDontSee('Oferta de outra guilda');
    }

    public function test_sorteio_sem_dungeons_retorna_erro_sem_modificar_dados(): void
    {
        Dungeon::query()->delete();
        $this->from('/?guilda=1')->post('/guildas/1/expedicao/sortear', ['aventureiro_id' => 1])
            ->assertSessionHasErrors('dungeon')->assertSessionMissing('expedicao.oferta');
        $this->assertSame(3, Quest::count());
    }

    public function test_api_de_quests_usa_os_nomes_atuais_do_banco(): void
    {
        $this->postJson('/api/quests', ['aventureiro_id' => 1, 'dungeon_id' => 2, 'data_quest' => '2026-10-01', 'sucesso' => true])
            ->assertCreated()->assertJsonPath('data.dungeon_id', 2)->assertJsonPath('data.data_quest', '2026-10-01')
            ->assertJsonPath('data.dungeon.nome', 'Torre Esquecida');
        $this->assertSame(4, Quest::count());
        $this->assertSame(3, Dungeon::findOrFail(2)->quests()->count());
    }
}
