<?php

use App\Http\Controllers\Api\AventureiroController;
use App\Http\Controllers\Api\DungeonController;
use App\Http\Controllers\Api\GuildaController;
use App\Http\Controllers\Api\QuestController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// CRUD REST das 4 tabelas (GET/POST /api/guildas, GET/PUT/DELETE /api/guildas/{guilda}, ...)
Route::apiResource('guildas', GuildaController::class);
Route::apiResource('aventureiros', AventureiroController::class);
Route::apiResource('dungeons', DungeonController::class);
Route::apiResource('quests', QuestController::class);
