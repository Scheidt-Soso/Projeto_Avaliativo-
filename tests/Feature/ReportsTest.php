<?php

namespace Tests\Feature;

use App\Models\Aventureiro;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        DB::statement("ATTACH DATABASE ':memory:' AS rpg_trabalho_bd");
        DB::statement("CREATE TABLE rpg_trabalho_bd.aventureiros (id INTEGER PRIMARY KEY, nickname TEXT, classe TEXT, nivel INTEGER, guilda_id INTEGER, senha TEXT, role TEXT DEFAULT 'jogador')");
        DB::statement("CREATE VIEW rpg_trabalho_bd.vw_matheusayres_ranking_xp AS SELECT 'Lyriel' AS nickname, 1500 AS xp_total_ganho, 1 AS quests_concluidas");
        DB::statement("CREATE VIEW rpg_trabalho_bd.vw_matheusayres_aventureiros_guildas AS SELECT 'Lyriel' AS nickname, 'Mago' AS classe, 2 AS nivel, 'Corvos' AS nome_guilda");
        DB::statement("CREATE VIEW rpg_trabalho_bd.vw_matheusayres_aventureiros_inativos AS SELECT 'SemQuest' AS nickname, 'Mago' AS classe");
        DB::statement("CREATE VIEW rpg_trabalho_bd.vw_matheusayres_dungeons_alto_xp AS SELECT 'Torre' AS nome, 'Média' AS dificuldade, 1500 AS xp_recompensa");
        DB::statement("CREATE VIEW rpg_trabalho_bd.vw_matheusayres_historico_sucesso AS SELECT '2026-10-01' AS data_quest, 'Lyriel' AS nickname, 'Torre' AS dungeon, 1500 AS xp_recompensa");
        $this->actingAs(Aventureiro::create(['nickname' => 'Conta', 'classe' => 'Mago', 'nivel' => 1, 'role' => 'jogador']));
    }

    public function test_cinco_relatorios_renderizam_os_campos_das_views(): void
    {
        foreach (['ranking' => '1.500 XP', 'vinculos' => 'Corvos', 'inativos' => 'SemQuest', 'dungeons' => 'Torre', 'sucessos' => '01/10/2026'] as $aba => $conteudo) {
            $this->get('/relatorios?aba='.$aba)->assertOk()->assertSee($conteudo);
        }
    }

    public function test_busca_aplica_filtro_e_jogador_nao_atualiza_ranking(): void
    {
        $this->get('/relatorios?aba=ranking&busca=nao-existe')->assertOk()->assertSee('Nenhum registro corresponde à busca.');
        $this->post('/relatorios/atualizar')->assertForbidden();
        $this->getJson('/relatorios?aba=nao-existe')->assertUnprocessable();
    }
}
