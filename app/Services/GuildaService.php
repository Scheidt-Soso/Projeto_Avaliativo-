<?php

namespace App\Services;

use App\Models\Guilda;

class GuildaService extends CrudService
{
    protected function model(): string
    {
        return Guilda::class;
    }
}
