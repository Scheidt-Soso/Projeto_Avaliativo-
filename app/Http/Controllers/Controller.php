<?php

namespace App\Http\Controllers;

use Illuminate\Database\QueryException;

abstract class Controller
{
    protected function mensagemDoBanco(QueryException $e): string
    {
        return \App\Support\DatabaseError::message($e);
    }
}
