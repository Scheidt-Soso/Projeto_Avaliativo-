<?php

namespace Tests\Feature;

use App\Models\Aventureiro;
use App\Models\Guilda;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AventureiroSelectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Reproduz o schema original em memória; nunca acessa o Neon nos testes.
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        DB::statement("ATTACH DATABASE ':memory:' AS rpg_trabalho_bd");
        DB::statement('CREATE TABLE rpg_trabalho_bd.guildas (id INTEGER PRIMARY KEY, nome TEXT, nivel_minimo INTEGER, data_fundacao TEXT)');
        DB::statement('CREATE TABLE rpg_trabalho_bd.aventureiros (id INTEGER PRIMARY KEY, nickname TEXT UNIQUE, classe TEXT, nivel INTEGER, guilda_id INTEGER)');
        Guilda::create(['id' => 1, 'nome' => 'Lâminas Rubras', 'nivel_minimo' => 5, 'data_fundacao' => '2020-01-15']);
        Aventureiro::create(['nickname' => 'Kaelthas', 'classe' => 'Guerreiro', 'nivel' => 12, 'guilda_id' => 1]);
        Aventureiro::create(['nickname' => 'Lyriel', 'classe' => 'Mago', 'nivel' => 25, 'guilda_id' => null]);
    }

    public function test_pagina_exibe_dados_e_guilda_do_banco(): void
    {
        $this->get('/')->assertOk()->assertSee('Kaelthas')->assertSee('Lâminas Rubras')->assertSee('Lyriel')->assertSee('Sem guilda');
    }

    public function test_busca_e_classe_sao_aplicadas_em_conjunto(): void
    {
        $this->get('/?busca=KAEL&classe=Guerreiro')->assertOk()->assertSee('Kaelthas')->assertDontSee('Lyriel');
        $this->get('/?busca=Kael&classe=Mago')->assertOk()->assertSee('Nenhum nome respondeu ao chamado.');
    }

    public function test_busca_trata_curingas_como_texto_literal(): void
    {
        $this->get('/?busca=%25')->assertOk()->assertSee('Nenhum nome respondeu ao chamado.')->assertDontSee('Kaelthas');
    }

    public function test_selecao_e_mantida_na_sessao_sem_modificar_o_personagem(): void
    {
        $antes = Aventureiro::findOrFail(1)->getAttributes();
        $this->post('/aventureiro/selecionar', ['aventureiro_id' => 1])
            ->assertRedirect(route('aventureiros.escolher'))->assertSessionHas('aventureiro_id', 1);
        $this->get('/')->assertOk()->assertSee('Escolha salva nesta sessão')->assertSee('O destino tem um nome.');
        $this->assertSame($antes, Aventureiro::findOrFail(1)->getAttributes());
    }

    public function test_selecao_invalida_preserva_a_escolha_anterior(): void
    {
        $this->withSession(['aventureiro_id' => 1])->post('/aventureiro/selecionar', ['aventureiro_id' => 999])
            ->assertSessionHasErrors('aventureiro_id')->assertSessionHas('aventureiro_id', 1);
    }

    public function test_retrato_pode_ser_personalizado_sem_mudar_a_classe(): void
    {
        config(['appearance.characters.1.portrait' => 'images/portraits/pedro.webp']);
        $this->get('/')->assertOk()->assertSee('images/portraits/pedro.webp')->assertSee('Retrato de Kaelthas');
        $this->assertSame('Guerreiro', Aventureiro::findOrFail(1)->classe);
    }

    public function test_paginacao_preserva_os_filtros(): void
    {
        for ($i = 0; $i < 13; $i++) {
            Aventureiro::create(['nickname' => 'Sentinela'.$i, 'classe' => 'Guerreiro', 'nivel' => 1]);
        }
        $response = $this->get('/?busca=Sentinela&classe=Guerreiro');
        $response->assertOk()->assertSee('busca=Sentinela&amp;classe=Guerreiro&amp;page=2', false);
        $this->get('/?busca=Sentinela&classe=Guerreiro&page=2')->assertOk()->assertSee('Sentinela12')->assertDontSee('Sentinela0');
    }

    public function test_api_existente_lista_aventureiros_com_guilda_em_json(): void
    {
        $this->getJson('/api/aventureiros')->assertOk()->assertJsonPath('data.0.nickname', 'Kaelthas')
            ->assertJsonPath('data.0.guilda.nome', 'Lâminas Rubras')->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_api_existente_retorna_404_para_aventureiro_inexistente(): void
    {
        $this->getJson('/api/aventureiros/999')->assertNotFound()->assertJsonStructure(['message']);
    }
}
