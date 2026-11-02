<?php

namespace Tests\Feature;

use App\Models\Aventureiro;
use App\Models\Guilda;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    private Aventureiro $jogador;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        DB::statement("ATTACH DATABASE ':memory:' AS rpg_trabalho_bd");
        DB::statement('CREATE TABLE rpg_trabalho_bd.guildas (id INTEGER PRIMARY KEY, nome TEXT, nivel_minimo INTEGER, data_fundacao TEXT)');
        DB::statement("CREATE TABLE rpg_trabalho_bd.aventureiros (id INTEGER PRIMARY KEY, nickname TEXT UNIQUE NOT NULL, classe TEXT NOT NULL, nivel INTEGER DEFAULT 1, guilda_id INTEGER, senha TEXT, role TEXT NOT NULL DEFAULT 'jogador' CHECK (role IN ('jogador', 'admin')))");
        Guilda::create(['nome' => 'Lâminas Rubras', 'nivel_minimo' => 1]);
        $this->jogador = Aventureiro::create([
            'nickname' => 'Sentinela', 'classe' => 'Guerreiro', 'nivel' => 12,
            'guilda_id' => 1, 'senha' => Hash::make('juramento-seguro'), 'role' => 'jogador',
        ]);
    }

    public function test_visitantes_precisam_entrar_para_acessar_painel_e_api(): void
    {
        $this->get('/?guilda=1')->assertRedirect(route('login'));
        $this->get('/aventureiros')->assertRedirect(route('login'));
        $this->post('/guildas/1/expedicao/selecionar', ['aventureiro_id' => 1, 'dungeon_id' => 1])->assertRedirect(route('login'));
        foreach (['guildas', 'aventureiros', 'dungeons', 'quests'] as $recurso) {
            $this->getJson('/api/'.$recurso)->assertUnauthorized();
            $this->postJson('/api/'.$recurso, [])->assertUnauthorized();
        }
    }

    public function test_formularios_publicos_estao_disponiveis(): void
    {
        $this->get('/login')->assertOk()->assertSee('name="nickname"', false)->assertSee('name="senha"', false);
        $this->get('/register')->assertOk()->assertViewHas('classes', fn ($classes) => $classes->all() === ['Guerreiro']);
    }

    public function test_login_usa_nickname_e_senha_do_banco_e_renova_a_sessao(): void
    {
        $this->withSession(['aventureiro_id' => 99, 'expedicao.selecao' => ['guilda_id' => 99]]);
        $sessaoAnterior = session()->getId();
        $this->post('/login', ['nickname' => 'Sentinela', 'senha' => 'juramento-seguro'])
            ->assertRedirect(route('guildas.dashboard', ['guilda' => 1]))
            ->assertSessionMissing('expedicao')->assertSessionMissing('aventureiro_id');
        $this->assertAuthenticatedAs($this->jogador);
        $this->assertNotSame($sessaoAnterior, session()->getId());
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/user')->assertOk()->assertJsonPath('nickname', 'Sentinela')->assertJsonMissingPath('senha');
        $this->assertSame(12, $this->jogador->fresh()->nivel);
    }

    public function test_login_retorna_a_pagina_protegida_solicitada(): void
    {
        $this->get('/?guilda=8')->assertRedirect(route('login'));
        $this->post('/login', ['nickname' => 'Sentinela', 'senha' => 'juramento-seguro'])
            ->assertRedirect(rtrim(config('app.url'), '/').'/?guilda=8');
    }

    public function test_senha_incorreta_nao_autentica_nem_fica_no_old_input(): void
    {
        $this->from('/login')->post('/login', ['nickname' => 'Sentinela', 'senha' => 'segredo-incorreto'])
            ->assertRedirect('/login')->assertSessionHasErrors(['nickname' => 'Nickname ou senha incorretos.'])
            ->assertSessionHas('_old_input.nickname', 'Sentinela')->assertSessionMissing('_old_input.senha');
        $this->assertGuest();
    }

    public function test_aventureiro_legado_sem_senha_e_nickname_inexistente_nao_entram(): void
    {
        Aventureiro::create(['nickname' => 'Legado', 'classe' => 'Mago', 'senha' => null]);
        foreach (['Legado', 'Inexistente'] as $nickname) {
            $this->post('/login', ['nickname' => $nickname, 'senha' => 'qualquer-senha'])
                ->assertSessionHasErrors(['nickname' => 'Nickname ou senha incorretos.']);
            $this->assertGuest();
        }
    }

    public function test_login_limita_tentativas_e_libera_apos_o_intervalo(): void
    {
        $this->freezeTime();
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['nickname' => 'Sentinela', 'senha' => 'incorreta'])->assertSessionHasErrors('nickname');
        }
        $this->post('/login', ['nickname' => 'Sentinela', 'senha' => 'juramento-seguro'])
            ->assertSessionHasErrors(['nickname' => 'Muitas tentativas. Aguarde 60 segundos para tentar novamente.']);
        $this->assertGuest();
        $this->travel(61)->seconds();
        $this->post('/login', ['nickname' => 'Sentinela', 'senha' => 'juramento-seguro'])->assertRedirect();
        $this->assertAuthenticatedAs($this->jogador);
    }

    public function test_cadastro_cria_aventureiro_com_hash_sem_aceitar_nivel_guilda_ou_role_enviados(): void
    {
        $this->post('/register', [
            'nickname' => 'NovaSentinela', 'classe' => 'Mago',
            'senha' => 'juramento-novo', 'senha_confirmation' => 'juramento-novo',
            'role' => 'admin', 'nivel' => 99, 'guilda_id' => 1,
        ])->assertRedirect(route('login'))->assertSessionHas('sucesso');
        $aventureiro = Aventureiro::where('nickname', 'NovaSentinela')->firstOrFail();
        $this->assertSame('jogador', $aventureiro->role);
        $this->assertSame(1, $aventureiro->nivel);
        $this->assertNull($aventureiro->guilda_id);
        $this->assertTrue(Hash::check('juramento-novo', $aventureiro->senha));
        $this->assertArrayNotHasKey('senha', $aventureiro->toArray());
        $this->assertGuest();
    }

    public function test_cadastro_rejeita_nickname_duplicado_e_oculta_confirmacao_da_senha(): void
    {
        $this->post('/register', ['nickname' => 'Sentinela', 'classe' => 'Mago', 'senha' => 'senha-segura', 'senha_confirmation' => 'diferente'])
            ->assertSessionHasErrors(['nickname', 'senha'])
            ->assertSessionMissing('_old_input.senha')->assertSessionMissing('_old_input.senha_confirmation');
        $this->assertSame(1, Aventureiro::count());
    }

    public function test_espacos_na_senha_sao_preservados_no_cadastro_e_no_login(): void
    {
        $senha = '  juramento seguro  ';
        $this->post('/register', ['nickname' => 'Espacos', 'classe' => 'Mago', 'senha' => $senha, 'senha_confirmation' => $senha])->assertRedirect(route('login'));
        $this->post('/login', ['nickname' => 'Espacos', 'senha' => $senha])->assertRedirect();
        $this->assertAuthenticatedAs(Aventureiro::where('nickname', 'Espacos')->firstOrFail());
    }

    public function test_cadastro_rejeita_senha_invalida_ou_maior_que_o_limite_do_bcrypt(): void
    {
        foreach ([['valor-invalido'], str_repeat('é', 40)] as $senha) {
            $this->post('/register', ['nickname' => 'SemCadastro', 'classe' => 'Mago', 'senha' => $senha, 'senha_confirmation' => $senha])
                ->assertSessionHasErrors('senha')->assertSessionMissing('_old_input.senha');
        }
        $this->assertSame(1, Aventureiro::count());
    }

    public function test_logout_invalida_sessao_e_impede_retorno_ao_painel(): void
    {
        $this->actingAs($this->jogador)->withSession(['expedicao.selecao' => ['guilda_id' => 1], '_token' => 'token-anterior']);
        $this->post('/logout')->assertRedirect(route('login'))->assertSessionMissing('expedicao');
        $this->assertGuest();
        $this->assertNotSame('token-anterior', session()->token());
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_jogador_consulta_api_mas_nao_altera_cadastros(): void
    {
        $this->actingAs($this->jogador)->getJson('/api/aventureiros')->assertOk()->assertJsonMissingPath('data.0.senha');
        foreach (['guildas', 'aventureiros', 'dungeons', 'quests'] as $recurso) {
            $this->postJson('/api/'.$recurso, [])->assertForbidden();
        }
        $this->putJson('/api/aventureiros/1', ['role' => 'admin'])->assertForbidden();
        $this->deleteJson('/api/guildas/1')->assertForbidden();
        $this->assertSame(1, Aventureiro::count());
        $this->assertSame('jogador', $this->jogador->fresh()->role);
    }

    public function test_admin_do_banco_pode_usar_crud_e_usuario_autenticado_nao_reabre_login(): void
    {
        $this->jogador->update(['role' => 'admin']);
        $this->actingAs($this->jogador)->postJson('/api/guildas', ['nome' => 'Nova Bandeira', 'nivel_minimo' => 1])->assertCreated();
        $this->get('/login')->assertRedirect(route('guildas.dashboard'));
        $this->get('/register')->assertRedirect(route('guildas.dashboard'));
    }
}
