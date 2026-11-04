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

class PerfilTest extends TestCase
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
        Guilda::create(['nome' => 'Veteranos', 'nivel_minimo' => 10]);
        Aventureiro::create(['nickname' => 'OutroJogador', 'classe' => 'Paladino', 'nivel' => 30]);
        Aventureiro::create(['nickname' => 'MeuAventureiro', 'classe' => 'Mago', 'nivel' => 1]);
        Dungeon::create(['nome' => 'Caverna Certa', 'dificuldade' => 'Fácil', 'xp_recompensa' => 1500]);
        $this->actingAs(Aventureiro::findOrFail(2));
    }

    public function test_aventureiro_sem_guilda_chega_ao_perfil_e_pode_consultar_o_quartel(): void
    {
        $this->get('/')->assertRedirect(route('perfil.show'));
        $this->get('/perfil')->assertOk()->assertSee('MeuAventureiro')->assertSee('Ainda sem guilda')->assertSee('REGISTRAR QUEST');
        $this->get('/?guilda=1')->assertOk()->assertSee('Veteranos');
    }

    public function test_registro_de_quest_usa_proprio_id_destino_exato_e_nao_duplica(): void
    {
        $dados = ['submission_token' => (string) Str::uuid(), 'aventureiro_id' => 1, 'dungeon_id' => 1, 'data_quest' => '2026-10-01', 'sucesso' => true];
        $this->post('/perfil/quests', $dados)->assertRedirect(route('perfil.show'));
        $this->post('/perfil/quests', $dados)->assertRedirect(route('perfil.show'));
        $this->assertSame(1, Quest::count());
        $this->assertSame(2, Quest::firstOrFail()->aventureiro_id);
        $this->assertSame(1, Quest::firstOrFail()->dungeon_id);
        $this->assertSame(2, Aventureiro::findOrFail(2)->nivel);
        $this->assertSame(30, Aventureiro::findOrFail(1)->nivel);
        $this->get('/perfil')->assertOk()->assertSee('1.500');
    }

    public function test_vinculo_valida_nivel_e_muda_so_o_aventureiro_autenticado(): void
    {
        $this->post('/perfil/guilda', ['guilda_id' => 1, 'aventureiro_id' => 1])->assertSessionHasErrors('guilda_id');
        Aventureiro::findOrFail(2)->update(['nivel' => 10]);
        $this->post('/perfil/guilda', ['guilda_id' => 1, 'aventureiro_id' => 1])->assertRedirect(route('guildas.dashboard', ['guilda' => 1]));
        $this->assertSame(1, Aventureiro::findOrFail(2)->guilda_id);
        $this->assertNull(Aventureiro::findOrFail(1)->guilda_id);
    }

    public function test_quest_futura_ou_destino_invalido_e_rejeitado(): void
    {
        $this->post('/perfil/quests', ['submission_token' => (string) Str::uuid(), 'dungeon_id' => 999, 'data_quest' => now()->addDay()->toDateString(), 'sucesso' => true])->assertSessionHasErrors(['dungeon_id', 'data_quest']);
        $this->assertSame(0, Quest::count());
    }
}
