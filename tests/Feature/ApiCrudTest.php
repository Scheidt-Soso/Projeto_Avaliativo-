<?php

namespace Tests\Feature;

use App\Models\Aventureiro;
use App\Models\Dungeon;
use App\Models\Guilda;
use App\Models\Quest;
use App\Services\ProcedureService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\Support\FakeProcedures;
use Tests\TestCase;

class ApiCrudTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $this->app->instance(ProcedureService::class, new FakeProcedures);
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

    public function test_admin_cria_atualiza_lista_e_exclui_guilda_pela_api(): void
    {
        Sanctum::actingAs(Aventureiro::findOrFail(3));

        $criada = $this->postJson('/api/admin/guildas', [
            'nome' => 'Guarda de Ferro',
            'nivel_minimo' => 4,
            'data_fundacao' => '2026-01-15',
        ])->assertCreated()
            ->assertJsonPath('data.nome', 'Guarda de Ferro')
            ->assertJsonPath('data.nivel_minimo', 4)
            ->assertJsonPath('data.data_fundacao', '2026-01-15');

        $id = $criada->json('data.id');
        $this->assertSame('Guarda de Ferro', Guilda::findOrFail($id)->nome);

        $this->getJson('/api/admin/guildas/'.$id)->assertOk()->assertJsonPath('data.nome', 'Guarda de Ferro');
        $this->getJson('/api/admin/guildas')->assertOk()->assertJsonFragment(['nome' => 'Guarda de Ferro']);
        $this->patchJson('/api/admin/guildas/'.$id, ['nome' => 'Guarda de Aço'])->assertOk()->assertJsonPath('data.nome', 'Guarda de Aço');
        $this->deleteJson('/api/admin/guildas/'.$id)->assertNoContent();
        $this->assertNull(Guilda::find($id));
    }

    public function test_admin_cria_atualiza_lista_e_exclui_dungeon_pela_api(): void
    {
        Sanctum::actingAs(Aventureiro::findOrFail(3));

        $criada = $this->postJson('/api/admin/dungeons', [
            'nome' => 'Catacumbas',
            'dificuldade' => 'Alta',
            'xp_recompensa' => 120,
        ])->assertCreated()
            ->assertJsonPath('data.nome', 'Catacumbas')
            ->assertJsonPath('data.dificuldade', 'Alta')
            ->assertJsonPath('data.xp_recompensa', 120);

        $id = $criada->json('data.id');
        $this->assertSame('Catacumbas', Dungeon::findOrFail($id)->nome);

        $this->getJson('/api/admin/dungeons/'.$id)->assertOk()->assertJsonPath('data.nome', 'Catacumbas');
        $this->getJson('/api/admin/dungeons')->assertOk()->assertJsonFragment(['nome' => 'Catacumbas']);
        $this->patchJson('/api/admin/dungeons/'.$id, ['xp_recompensa' => 150])->assertOk()->assertJsonPath('data.xp_recompensa', 150);
        $this->deleteJson('/api/admin/dungeons/'.$id)->assertNoContent();
        $this->assertNull(Dungeon::find($id));
    }

    public function test_admin_cria_atualiza_lista_e_exclui_aventureiro_pela_api(): void
    {
        Sanctum::actingAs(Aventureiro::findOrFail(3));

        $criado = $this->postJson('/api/admin/aventureiros', [
            'nickname' => 'Nova Lâmina',
            'classe' => 'Ladino',
            'nivel' => 3,
            'role' => 'jogador',
            'guilda_id' => 1,
            'senha' => 'segredo123',
            'senha_confirmation' => 'segredo123',
        ])->assertCreated()
            ->assertJsonPath('data.nickname', 'Nova Lâmina')
            ->assertJsonPath('data.nivel', 3)
            ->assertJsonPath('data.guilda_id', 1)
            ->assertJsonPath('data.guilda.nome', 'Lâminas Rubras')
            ->assertJsonMissingPath('data.senha');

        $id = $criado->json('data.id');
        $aventureiro = Aventureiro::findOrFail($id);
        $this->assertSame('jogador', $aventureiro->role);
        $this->assertTrue(Hash::check('segredo123', $aventureiro->senha));

        $this->getJson('/api/admin/aventureiros/'.$id)->assertOk()->assertJsonPath('data.nickname', 'Nova Lâmina');
        $this->getJson('/api/admin/aventureiros')->assertOk()->assertJsonFragment(['nickname' => 'Nova Lâmina']);
        $this->patchJson('/api/admin/aventureiros/'.$id, [
            'nickname' => 'Nova Lâmina II',
            'role' => 'admin',
        ])->assertOk()->assertJsonPath('data.nickname', 'Nova Lâmina II');
        $this->assertSame('admin', Aventureiro::findOrFail($id)->role);
        $this->deleteJson('/api/admin/aventureiros/'.$id)->assertNoContent();
        $this->assertNull(Aventureiro::find($id));
    }

    public function test_admin_cria_atualiza_lista_e_exclui_quest_pela_api(): void
    {
        Sanctum::actingAs(Aventureiro::findOrFail(3));

        $criada = $this->postJson('/api/admin/quests', [
            'aventureiro_id' => 1,
            'dungeon_id' => 1,
            'data_quest' => now()->toDateString(),
            'sucesso' => true,
        ])->assertCreated()
            ->assertJsonPath('data.aventureiro_id', 1)
            ->assertJsonPath('data.dungeon_id', 1)
            ->assertJsonPath('data.dungeon.nome', 'Cripta')
            ->assertJsonPath('data.sucesso', true);

        $id = $criada->json('data.id');
        $this->assertSame(1, Quest::count());

        $this->getJson('/api/admin/quests/'.$id)->assertOk()->assertJsonPath('data.dungeon.nome', 'Cripta');
        $this->getJson('/api/admin/quests')->assertOk()->assertJsonFragment(['dungeon_id' => 1]);
        $this->patchJson('/api/admin/quests/'.$id, ['sucesso' => false])->assertOk()->assertJsonPath('data.sucesso', false);
        $this->assertFalse((bool) Quest::findOrFail($id)->sucesso);
        $this->deleteJson('/api/admin/quests/'.$id)->assertNoContent();
        $this->assertNull(Quest::find($id));
    }
}
