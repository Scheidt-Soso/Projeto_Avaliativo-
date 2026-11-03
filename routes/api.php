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

Route::prefix('jogador')->group(function () {
    Route::get('/guildas', [GuildaController::class, 'listarGuildasJogador']);
    Route::get('/aventureiros', [AventureiroController::class, 'listarAventureirosJogador']);
    Route::get('/dungeons', [DungeonController::class, 'listarDungeonsJogador']);
    Route::get('/quests', [QuestController::class, 'listarQuestsJogador']);

    Route::post('/aventureiros/{aventureiro}/guilda', [AventureiroController::class, 'vincularGuilda']);
    
});

Route::prefix('admin')->group(function () {
    // Rotas para o administrador visualizar todas as guildas, aventureiros, dungeons e quests
    Route::get('/guildas', [GuildaController::class, 'listarGuildasAdmin']);
    Route::get('/aventureiros', [AventureiroController::class, 'listarAventureirosAdmin']);
    Route::get('/dungeons', [DungeonController::class, 'listarDungeonsAdmin']);
    Route::get('/quests', [QuestController::class, 'listarQuestsAdmin']);
    
});

Route::post('/quests/{quest}/registrar', [QuestController::class, 'registrarQuest']);