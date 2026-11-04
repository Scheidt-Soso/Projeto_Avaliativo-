<?php

namespace Tests\Feature;

use App\Models\Aventureiro;
use App\Models\Dungeon;
use App\Models\Guilda;
use App\Models\Quest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiAuthorizationTest extends TestCase
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
        DB::statement('CREATE TABLE personal_access_tokens (id INTEGER PRIMARY KEY AUTOINCREMENT, tokenable_type TEXT NOT NULL, tokenable_id INTEGER NOT NULL, name TEXT NOT NULL, token TEXT NOT NULL UNIQUE, abilities TEXT, last_used_at TEXT, expires_at TEXT, created_at TEXT, updated_at TEXT)');

        Guilda::create(['id' => 1, 'nome' => 'Lâminas Rubras', 'nivel_minimo' => 1]);
        Aventureiro::create(['id' => 1, 'nickname' => 'Jogador', 'classe' => 'Guerreiro', 'nivel' => 5, 'role' => 'jogador', 'senha' => Hash::make('senha123')]);
        Aventureiro::create(['id' => 2, 'nickname' => 'Outro', 'classe' => 'Mago', 'nivel' => 5, 'role' => 'jogador', 'senha' => Hash::make('senha123')]);
        Aventureiro::create(['id' => 3, 'nickname' => 'Admin', 'classe' => 'Paladino', 'nivel' => 10, 'role' => 'admin', 'senha' => Hash::make('senha123')]);
        Dungeon::create(['id' => 1, 'nome' => 'Cripta', 'dificuldade' => 'Média', 'xp_recompensa' => 40]);
    }

    public function test_dungeons_e_publica(): void
    {
        $this->getJson('/api/dungeons')->assertOk()->assertJsonPath('data.0.nome', 'Cripta');
    }

    public function test_rotas_de_jogador_exigem_token(): void
    {
        $this->getJson('/api/jogador/guildas')->assertUnauthorized();
    }

    public function test_jogador_le_recursos_mas_nao_acessa_admin(): void
    {
        Sanctum::actingAs(Aventureiro::findOrFail(1));

        $this->getJson('/api/jogador/guildas')->assertOk();
        $this->getJson('/api/admin/guildas')->assertForbidden();
    }

    public function test_admin_acessa_rotas_de_admin(): void
    {
        Sanctum::actingAs(Aventureiro::findOrFail(3));

        $this->getJson('/api/admin/guildas')->assertOk();
    }

    public function test_jogador_nao_define_resultado_nem_pela_api_antiga_ou_administrativa(): void
    {
        Sanctum::actingAs(Aventureiro::findOrFail(1));

        foreach ([true, false] as $sucesso) {
            foreach (['/api/jogador/quests', '/api/jogador/quests/registrar', '/api/admin/quests', '/api/admin/quests/registrar'] as $rota) {
                $this->postJson($rota, [
                    'aventureiro_id' => 2, 'dungeon_id' => 1,
                    'data_quest' => '2026-11-01', 'sucesso' => $sucesso,
                    'role' => 'admin', 'nivel' => 99, 'xp_total' => 99999,
                ])->assertForbidden();
            }
        }

        $this->assertSame(0, Quest::count());
        $this->assertSame(5, Aventureiro::findOrFail(1)->nivel);
        $this->assertSame(5, Aventureiro::findOrFail(2)->nivel);
        $this->assertSame('jogador', Aventureiro::findOrFail(1)->role);
    }

    public function test_jogador_nao_corrige_nem_exclui_resultado_existente(): void
    {
        $quest = Quest::create(['aventureiro_id' => 1, 'dungeon_id' => 1, 'data_quest' => '2026-11-01', 'sucesso' => false]);
        Sanctum::actingAs(Aventureiro::findOrFail(1));
        $this->patchJson('/api/admin/quests/'.$quest->id, ['sucesso' => true])->assertForbidden();
        $this->deleteJson('/api/admin/quests/'.$quest->id)->assertForbidden();
        $this->assertFalse($quest->fresh()->sucesso);
        $this->assertSame(5, Aventureiro::findOrFail(1)->nivel);
    }

    public function test_login_devolve_token_e_logout_revoga(): void
    {
        $login = $this->postJson('/api/login', ['nickname' => 'Jogador', 'senha' => 'senha123'])
            ->assertOk()
            ->assertJsonStructure(['aventureiro', 'token']);

        $token = $login->json('token');

        $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/user')->assertOk();
        $this->withHeader('Authorization', 'Bearer '.$token)->postJson('/api/logout')->assertOk();
        $this->assertSame(0, Aventureiro::findOrFail(1)->tokens()->count());
        Auth::forgetGuards();
        $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/user')->assertUnauthorized();
    }

    public function test_login_invalido_e_rejeitado(): void
    {
        $this->postJson('/api/login', ['nickname' => 'Jogador', 'senha' => 'errada'])
            ->assertUnauthorized();
    }

    public function test_api_rejeita_quest_futura_e_ids_nao_numericos_sem_erro_de_servidor(): void
    {
        Sanctum::actingAs(Aventureiro::findOrFail(3));
        $this->postJson('/api/admin/quests', ['aventureiro_id' => 1, 'dungeon_id' => 1, 'data_quest' => now()->addDay()->toDateString(), 'sucesso' => true])
            ->assertUnprocessable()->assertJsonValidationErrors('data_quest');
        $this->getJson('/api/dungeons/invalida')->assertNotFound();
        $this->assertSame(0, Quest::count());
    }

    public function test_api_admin_nao_permite_excluir_ou_rebaixar_a_propria_conta(): void
    {
        Sanctum::actingAs(Aventureiro::findOrFail(3));
        $this->patchJson('/api/admin/aventureiros/3', ['role' => 'jogador'])->assertUnprocessable()->assertJsonValidationErrors('role');
        $this->deleteJson('/api/admin/aventureiros/3')->assertUnprocessable();
        $this->assertSame('admin', Aventureiro::findOrFail(3)->role);
    }

    public function test_cadastro_da_api_usa_hash_papel_padrao_e_limite_de_senha(): void
    {
        $senha = str_repeat('é', 40);
        $this->postJson('/api/register', ['nickname' => 'Novo', 'classe' => 'Mago', 'senha' => $senha, 'senha_confirmation' => $senha])->assertUnprocessable();
        $cadastro = $this->postJson('/api/register', ['nickname' => 'Novo', 'classe' => 'Mago', 'senha' => 'nova-senha', 'senha_confirmation' => 'nova-senha', 'role' => 'admin', 'nivel' => 99])->assertCreated()->assertJsonPath('aventureiro.role', 'jogador')->assertJsonMissingPath('aventureiro.senha');
        $this->assertTrue(Hash::check('nova-senha', Aventureiro::where('nickname', 'Novo')->firstOrFail()->senha));
    }
}
