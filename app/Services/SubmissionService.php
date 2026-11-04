<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class SubmissionService
{
    public function criar(string $recurso, string $token, Closure $operacao): int
    {
        $key = 'cadastro:'.Auth::id().':'.$recurso.':'.$token;
        return Cache::lock($key.':lock', 30)->block(10, function () use ($key, $operacao) {
            if (($id = Cache::get($key)) !== null) return (int) $id;
            $id = (int) $operacao()->id;
            Cache::put($key, $id, now()->addMinutes(30));

            return $id;
        });
    }
}
