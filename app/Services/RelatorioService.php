<?php

namespace App\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class RelatorioService
{
    public function catalogo(): array
    {
        return [
            'ranking' => ['titulo' => 'Ranking de XP', 'descricao' => 'Aventureiros com quests bem-sucedidas, ordenados por XP acumulado.', 'view' => 'vw_matheusayres_ranking_xp', 'busca' => 'nickname', 'ordem' => 'xp_total_ganho', 'desc' => true, 'colunas' => ['nickname' => 'Aventureiro', 'xp_total_ganho' => 'XP total', 'quests_concluidas' => 'Quests concluídas']],
            'vinculos' => ['titulo' => 'Aventureiros e guildas', 'descricao' => 'Classes, níveis e bandeiras atuais de cada aventureiro.', 'view' => 'vw_matheusayres_aventureiros_guildas', 'busca' => 'nickname', 'ordem' => 'nickname', 'colunas' => ['nickname' => 'Aventureiro', 'classe' => 'Classe', 'nivel' => 'Nível', 'nome_guilda' => 'Guilda']],
            'inativos' => ['titulo' => 'Sem expedições', 'descricao' => 'Aventureiros que ainda não possuem nenhuma quest registrada.', 'view' => 'vw_matheusayres_aventureiros_inativos', 'busca' => 'nickname', 'ordem' => 'nickname', 'colunas' => ['nickname' => 'Aventureiro', 'classe' => 'Classe']],
            'dungeons' => ['titulo' => 'Dungeons de alto XP', 'descricao' => 'Destinos com recompensa acima da média das dungeons cadastradas.', 'view' => 'vw_matheusayres_dungeons_alto_xp', 'busca' => 'nome', 'ordem' => 'xp_recompensa', 'desc' => true, 'colunas' => ['nome' => 'Dungeon', 'dificuldade' => 'Dificuldade', 'xp_recompensa' => 'Recompensa']],
            'sucessos' => ['titulo' => 'Expedições vitoriosas', 'descricao' => 'As quests bem-sucedidas e suas recompensas registradas.', 'view' => 'vw_matheusayres_historico_sucesso', 'busca' => 'nickname', 'ordem' => 'data_quest', 'desc' => true, 'colunas' => ['data_quest' => 'Data', 'nickname' => 'Aventureiro', 'dungeon' => 'Dungeon', 'xp_recompensa' => 'XP']],
        ];
    }

    public function consultar(string $aba, string $busca): LengthAwarePaginator
    {
        $meta = $this->catalogo()[$aba];
        $query = DB::table('rpg_trabalho_bd.'.$meta['view']);
        if ($busca !== '') {
            $termo = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($busca)).'%';
            $query->whereRaw('LOWER('.$meta['busca'].") LIKE ? ESCAPE '!'", [$termo]);
        }

        return $query->orderBy($meta['ordem'], ($meta['desc'] ?? false) ? 'desc' : 'asc')->orderBy($meta['busca'])->paginate(20)->withQueryString();
    }

    public function atualizar(): void
    {
        app(ProcedureService::class)->recalcularNiveis([]);
    }
}
