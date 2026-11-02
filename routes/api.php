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

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('guildas', GuildaController::class)->middlewareFor(['store', 'update', 'destroy'], 'can:gerenciar-cadastros');
    Route::apiResource('aventureiros', AventureiroController::class)->middlewareFor(['store', 'update', 'destroy'], 'can:gerenciar-cadastros');
    Route::apiResource('dungeons', DungeonController::class)->middlewareFor(['store', 'update', 'destroy'], 'can:gerenciar-cadastros');
    Route::apiResource('quests', QuestController::class)->middlewareFor(['store', 'update', 'destroy'], 'can:gerenciar-cadastros');
});
