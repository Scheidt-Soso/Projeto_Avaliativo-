<?php

namespace App\Support;

use Illuminate\Database\QueryException;

class DatabaseError
{
    public static function message(QueryException $exception): string
    {
        $state = $exception->errorInfo[0] ?? $exception->getCode();
        if ($state === 'P0001') {
            $message = $exception->errorInfo[2] ?? $exception->getPrevious()?->errorInfo[2] ?? '';
            return preg_replace('/^(?:ERROR|ERRO):\s*/u', '', strtok($message, "\n")) ?: 'O banco não permitiu esta alteração.';
        }

        return match ($state) {
            '23505' => 'Já existe um registro com este identificador. Confira os dados.',
            '23503' => 'Um registro relacionado foi removido. Recarregue a página e tente novamente.',
            '23502', '23514' => 'Confira os campos obrigatórios e os valores informados.',
            default => 'Não foi possível concluir a operação. Tente novamente em instantes.',
        };
    }

    public static function isValidation(QueryException $exception): bool
    {
        return in_array($exception->errorInfo[0] ?? $exception->getCode(), ['P0001', '23505', '23503', '23502', '23514'], true);
    }
}
