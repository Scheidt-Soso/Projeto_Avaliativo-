<?php

namespace App\Services;

use App\Models\Aventureiro;
use App\Models\Dungeon;
use App\Models\Guilda;
use App\Models\Quest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CatalogoService
{
    public function listar(string $recurso, array $filtros): LengthAwarePaginator
    {
        $query = $this->query($recurso);
        if (($busca = $filtros['busca'] ?? '') !== '') {
            $termo = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($busca)).'%';
            $query->where(function ($query) use ($recurso, $termo) {
                if ($recurso === 'quests') {
                    $query->whereHas('aventureiro', fn ($q) => $q->whereRaw("LOWER(nickname) LIKE ? ESCAPE '!'", [$termo]))
                        ->orWhereHas('dungeon', fn ($q) => $q->whereRaw("LOWER(nome) LIKE ? ESCAPE '!'", [$termo]));
                } else {
                    $campo = $recurso === 'aventureiros' ? 'nickname' : 'nome';
                    $query->whereRaw("LOWER({$campo}) LIKE ? ESCAPE '!'", [$termo]);
                }
            });
        }
        foreach (['guilda_id', 'classe', 'dificuldade', 'aventureiro_id', 'dungeon_id', 'sucesso'] as $campo) {
            if (isset($filtros[$campo]) && $filtros[$campo] !== '') $query->where($campo, $filtros[$campo]);
        }

        return $query->orderByDesc($recurso === 'quests' ? 'data_quest' : 'id')->orderByDesc('id')->paginate(15)->withQueryString();
    }

    public function encontrar(string $recurso, int $id): Model
    {
        return $this->query($recurso)->findOrFail($id);
    }

    public function opcoes(): array
    {
        return [
            'guildas' => Guilda::query()->orderBy('nome')->pluck('nome', 'id'),
            'aventureiros' => Aventureiro::query()->orderBy('nickname')->pluck('nickname', 'id'),
            'dungeons' => Dungeon::query()->orderBy('nome')->pluck('nome', 'id'),
            'classes' => Aventureiro::query()->distinct()->orderBy('classe')->pluck('classe'),
            'dificuldades' => Dungeon::query()->whereNotNull('dificuldade')->distinct()->orderBy('dificuldade')->pluck('dificuldade'),
            'roles' => collect(['jogador' => 'Jogador', 'admin' => 'Administrador']),
            'resultados' => collect([1 => 'Sucesso', 0 => 'Falha']),
        ];
    }

    public function historico(Model $registro): mixed
    {
        if ($registro instanceof Quest) return collect();
        $query = Quest::query()->with(['aventureiro', 'dungeon']);
        if ($registro instanceof Guilda) $query->whereHas('aventureiro', fn ($q) => $q->where('guilda_id', $registro->id));
        elseif ($registro instanceof Aventureiro) $query->where('aventureiro_id', $registro->id);
        else $query->where('dungeon_id', $registro->id);

        return $query->orderByDesc('data_quest')->orderByDesc('id')->limit(10)->get();
    }

    public function impacto(Model $registro): string
    {
        return match (true) {
            $registro instanceof Guilda => $registro->aventureiros()->count().' aventureiro(s) ficarão sem guilda. As quests serão preservadas.',
            $registro instanceof Aventureiro => $registro->quests()->count().' quest(s) deste aventureiro serão removidas junto com ele.',
            $registro instanceof Dungeon => $registro->quests()->count().' quest(s) desta dungeon serão removidas. O XP e os níveis dos aventureiros envolvidos serão recalculados.',
            default => 'A quest será removida e o XP e nível do aventureiro serão recalculados.',
        };
    }

    private function query(string $recurso): Builder
    {
        $model = config("cadastros.{$recurso}.model");
        return $model::query()->with(match ($recurso) {
            'aventureiros' => ['guilda'], 'quests' => ['aventureiro', 'dungeon'], default => [],
        });
    }
}
