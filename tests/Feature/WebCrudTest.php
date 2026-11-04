<?php

namespace Tests\Feature;

use App\Models\Aventureiro;
use App\Models\Dungeon;
use App\Models\Guilda;
use App\Models\Quest;
use App\Services\ProcedureService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\FakeProcedures;
use Tests\TestCase;

class WebCrudTest extends TestCase
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
        Guilda::create(['nome' => 'Lâminas Rubras', 'nivel_minimo' => 1, 'data_fundacao' => '2020-01-15']);
        Aventureiro::create(['nickname' => 'Admin', 'classe' => 'Paladino', 'nivel' => 30, 'role' => 'admin', 'guilda_id' => 1]);
        Aventureiro::create(['nickname' => 'Lyriel', 'classe' => 'Mago', 'nivel' => 1, 'role' => 'jogador']);
        Dungeon::create(['nome' => 'Torre Esquecida', 'dificuldade' => 'Média', 'xp_recompensa' => 1500]);
        $this->actingAs(Aventureiro::findOrFail(1));
    }

    public function test_quatro_cadastros_possuem_listagem_formulario_e_ficha(): void
    {
        Quest::create(['aventureiro_id' => 2, 'dungeon_id' => 1, 'data_quest' => '2026-10-01', 'sucesso' => true]);
        foreach (['guildas', 'aventureiros', 'dungeons', 'quests'] as $recurso) {
            $this->get('/cadastros/'.$recurso)->assertOk()->assertSee('Ver ficha');
            $this->get('/cadastros/'.$recurso.'/create')->assertOk()->assertSee('CONFIRMAR REGISTRO');
            $this->get('/cadastros/'.$recurso.'/1')->assertOk()->assertSee('Editar');
            $this->get('/cadastros/'.$recurso.'/1/edit')->assertOk()->assertSee('SALVAR ALTERAÇÕES');
        }
    }

    public function test_guilda_pode_ser_criada_editada_e_excluida_com_confirmacao(): void
    {
        $this->post('/cadastros/guildas', ['submission_token' => (string) Str::uuid(), 'nome' => 'Nova Bandeira', 'nivel_minimo' => 1, 'data_fundacao' => '2026-01-01'])->assertRedirect();
        $guilda = Guilda::where('nome', 'Nova Bandeira')->firstOrFail();
        $this->put('/cadastros/guildas/'.$guilda->id, ['nome' => 'Bandeira Editada', 'nivel_minimo' => 2])->assertRedirect();
        $this->delete('/cadastros/guildas/'.$guilda->id, ['confirmacao' => 'incorreto'])->assertSessionHasErrors('confirmacao');
        $this->assertNotNull($guilda->fresh());
        $this->delete('/cadastros/guildas/'.$guilda->id, ['confirmacao' => 'Bandeira Editada'])->assertRedirect(route('cadastros.guildas.index'));
        $this->assertNull($guilda->fresh());
    }

    public function test_dungeon_tem_crud_completo_e_rejeita_recompensa_negativa(): void
    {
        $this->post('/cadastros/dungeons', ['submission_token' => (string) Str::uuid(), 'nome' => 'Caverna Nova', 'dificuldade' => 'Fácil', 'xp_recompensa' => -1])->assertSessionHasErrors('xp_recompensa');
        $this->post('/cadastros/dungeons', ['submission_token' => (string) Str::uuid(), 'nome' => 'Caverna Nova', 'dificuldade' => 'Fácil', 'xp_recompensa' => 500])->assertRedirect();
        $dungeon = Dungeon::where('nome', 'Caverna Nova')->firstOrFail();
        $this->put('/cadastros/dungeons/'.$dungeon->id, ['nome' => 'Caverna Editada', 'dificuldade' => 'Média', 'xp_recompensa' => 900])->assertRedirect();
        $this->delete('/cadastros/dungeons/'.$dungeon->id, ['confirmacao' => 'Caverna Editada'])->assertRedirect();
        $this->assertNull($dungeon->fresh());
    }

    public function test_aventureiro_tem_crud_e_vinculo_respeita_nivel_minimo(): void
    {
        $guilda = Guilda::create(['nome' => 'Veteranos', 'nivel_minimo' => 10]);
        $dados = ['submission_token' => (string) Str::uuid(), 'nickname' => 'Novato', 'classe' => 'Mago', 'nivel' => 1, 'role' => 'jogador', 'guilda_id' => $guilda->id];
        $this->post('/cadastros/aventureiros', $dados)->assertSessionHasErrors('guilda_id');
        $this->assertFalse(Aventureiro::where('nickname', 'Novato')->exists());
        $dados['nivel'] = 12;
        $this->post('/cadastros/aventureiros', $dados)->assertRedirect();
        $aventureiro = Aventureiro::where('nickname', 'Novato')->firstOrFail();
        $this->assertSame($guilda->id, $aventureiro->guilda_id);
        $this->put('/cadastros/aventureiros/'.$aventureiro->id, ['nickname' => 'Veterano', 'classe' => 'Mago', 'nivel' => 12, 'role' => 'jogador', 'guilda_id' => null])->assertRedirect();
        $this->assertNull($aventureiro->fresh()->guilda_id);
        $this->delete('/cadastros/aventureiros/'.$aventureiro->id, ['confirmacao' => 'Veterano'])->assertRedirect();
        $this->assertNull($aventureiro->fresh());
    }

    public function test_quest_tem_crud_progresso_e_protecao_contra_envio_duplicado(): void
    {
        $dados = ['submission_token' => (string) Str::uuid(), 'aventureiro_id' => 2, 'dungeon_id' => 1, 'data_quest' => '2026-10-01', 'sucesso' => true];
        $this->post('/cadastros/quests', $dados)->assertRedirect();
        $this->post('/cadastros/quests', $dados)->assertRedirect();
        $this->assertSame(1, Quest::count());
        $this->assertSame(2, Aventureiro::findOrFail(2)->nivel);
        $this->put('/cadastros/quests/1', ['aventureiro_id' => 2, 'dungeon_id' => 1, 'data_quest' => '2026-10-02', 'sucesso' => false])->assertRedirect();
        $this->assertSame(1, Aventureiro::findOrFail(2)->nivel);
        $this->delete('/cadastros/quests/1', ['confirmacao' => '#1'])->assertRedirect();
        $this->assertSame(0, Quest::count());
    }

    public function test_validacao_preserva_erros_sem_criar_registros_invalidos(): void
    {
        $this->post('/cadastros/quests', ['submission_token' => (string) Str::uuid(), 'aventureiro_id' => 999, 'dungeon_id' => 999, 'data_quest' => now()->addDay()->toDateString(), 'sucesso' => 'talvez'])->assertSessionHasErrors(['aventureiro_id', 'dungeon_id', 'data_quest', 'sucesso']);
        $this->assertSame(0, Quest::count());
        $this->post('/cadastros/guildas', ['submission_token' => (string) Str::uuid(), 'nome' => '', 'nivel_minimo' => 0])->assertSessionHasErrors(['nome', 'nivel_minimo']);
    }

    public function test_jogador_le_cadastros_mas_nao_consegue_alterar_nenhum(): void
    {
        $this->actingAs(Aventureiro::findOrFail(2));
        foreach (['guildas', 'aventureiros', 'dungeons', 'quests'] as $recurso) {
            $this->get('/cadastros/'.$recurso)->assertOk()->assertDontSee('CONFIRMAR REGISTRO');
            $this->get('/cadastros/'.$recurso.'/create')->assertForbidden();
            $this->post('/cadastros/'.$recurso, [])->assertForbidden();
            $this->put('/cadastros/'.$recurso.'/1', [])->assertForbidden();
            $this->delete('/cadastros/'.$recurso.'/1', [])->assertForbidden();
        }
    }

    public function test_busca_trata_curinga_literal_e_paginacao_preserva_filtros(): void
    {
        for ($i = 0; $i < 18; $i++) Guilda::create(['nome' => 'Sentinela '.$i, 'nivel_minimo' => 1]);
        $this->get('/cadastros/guildas?busca=Sentinela')->assertOk()->assertSee('busca=Sentinela&amp;page=2', false);
        $this->get('/cadastros/guildas?busca=%25')->assertOk()->assertSee('Nenhum registro encontrado.');
        $this->get('/cadastros/guildas/999')->assertNotFound();
    }

    public function test_admin_nao_remove_ou_demove_a_propria_conta(): void
    {
        $this->put('/cadastros/aventureiros/1', ['nickname' => 'Admin', 'classe' => 'Paladino', 'nivel' => 30, 'role' => 'jogador'])->assertSessionHasErrors('role');
        $this->delete('/cadastros/aventureiros/1', ['confirmacao' => 'Admin'])->assertSessionHasErrors('confirmacao');
        $this->assertSame('admin', Aventureiro::findOrFail(1)->role);
    }
}
