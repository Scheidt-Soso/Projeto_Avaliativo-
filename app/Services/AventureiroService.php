<?php

namespace App\Services;

use App\Models\Aventureiro;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class AventureiroService extends CrudService
{
    public function listarParaSelecao(string $busca = '', string $classe = ''): LengthAwarePaginator
    {
        return $this->query()
            ->when($busca !== '', fn ($query) => $query->whereRaw("LOWER(nickname) LIKE ? ESCAPE '!'", [
                '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($busca)).'%',
            ]))
            ->when($classe !== '', fn ($query) => $query->where('classe', $classe))
            ->orderBy('id')->paginate(12)->withQueryString();
    }

    public function classesDisponiveis(): Collection
    {
        return Aventureiro::query()->distinct()->orderBy('classe')->pluck('classe');
    }

    public function encontrarParaSelecao(int $id): Aventureiro
    {
        return $this->query()->findOrFail($id);
    }

    public function encontrarSelecionado(int $id): ?Aventureiro
    {
        return $this->query()->find($id);
    }

    protected function model(): string
    {
        return Aventureiro::class;
    }

    protected function relacionamentos(): array
    {
        return ['guilda'];
    }
}
