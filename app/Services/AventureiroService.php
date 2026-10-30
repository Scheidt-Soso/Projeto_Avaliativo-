<?php

namespace App\Services;

use App\Models\Aventureiro;

class AventureiroService extends CrudService
{
    protected function model(): string
    {
        return Aventureiro::class;
    }

    protected function relacionamentos(): array
    {
        return ['guilda'];
    }
}
