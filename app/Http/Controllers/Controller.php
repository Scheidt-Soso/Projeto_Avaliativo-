<?php

namespace App\Http\Controllers;

use Illuminate\Database\QueryException;

abstract class Controller
{
    protected function mensagemDoBanco(QueryException $e): string
    {
        return $e->getPrevious()?->errorInfo[2] ?? 'Não foi possível concluir a operação.';
    }
}
