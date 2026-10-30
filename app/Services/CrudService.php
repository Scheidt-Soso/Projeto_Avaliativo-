<?php

namespace App\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Base dos services: concentra o CRUD comum.
 *
 * Cada service filho só informa qual Model usa (model()) e, se quiser,
 * quais relacionamentos carregar junto (relacionamentos()).
 * Regras próprias de cada tabela entram sobrescrevendo ou adicionando métodos no filho.
 *
 * O controller NÃO fala com o Model direto: ele chama o service.
 */
abstract class CrudService
{
    /** @return class-string<Model> */
    abstract protected function model(): string;

    /** Relacionamentos carregados em toda consulta (evita N+1). */
    protected function relacionamentos(): array
    {
        return [];
    }

    protected function query(): Builder
    {
        return ($this->model())::query()->with($this->relacionamentos());
    }

    public function listar(int $porPagina = 15): LengthAwarePaginator
    {
        return $this->query()->orderBy('id')->paginate($porPagina);
    }

    public function criar(array $dados): Model
    {
        $registro = ($this->model())::create($dados);

        return $registro->load($this->relacionamentos());
    }

    public function exibir(Model $registro): Model
    {
        return $registro->load($this->relacionamentos());
    }

    public function atualizar(Model $registro, array $dados): Model
    {
        $registro->update($dados);

        return $registro->load($this->relacionamentos());
    }

    public function excluir(Model $registro): void
    {
        $registro->delete();
    }
}
