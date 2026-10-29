<?php

namespace App\Services;

use App\Models\Dungeon;

class DungeonService extends CrudService
{
    protected function model(): string
    {
        return Dungeon::class;
    }
}
