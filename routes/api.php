<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AventureiroController;
use App\Http\Controllers\Api\DungeonController;
use App\Http\Controllers\Api\GuildaController;
use App\Http\Controllers\Api\QuestController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:30,1');
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1');

Route::get('/dungeons', [DungeonController::class, 'index']);
Route::get('/dungeons/{dungeon}', [DungeonController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/quests/registrar', [QuestController::class, 'registrar']);
    Route::post('/aventureiros/{aventureiro}/guilda', [AventureiroController::class, 'vincularGuilda']);
    Route::apiResource('guildas', GuildaController::class)->middlewareFor(['store', 'update', 'destroy'], 'can:gerenciar-cadastros');
    Route::apiResource('aventureiros', AventureiroController::class)->middlewareFor(['store', 'update', 'destroy'], 'can:gerenciar-cadastros');
    Route::apiResource('dungeons', DungeonController::class)->except(['index', 'show'])->middleware('can:gerenciar-cadastros');
    Route::apiResource('quests', QuestController::class)->middlewareFor(['store', 'update', 'destroy'], 'can:gerenciar-cadastros');

    Route::prefix('jogador')->middleware('role:jogador')->group(function () {
        Route::get('/guildas', [GuildaController::class, 'index']);
        Route::get('/guildas/{guilda}', [GuildaController::class, 'show']);
        Route::get('/aventureiros', [AventureiroController::class, 'index']);
        Route::get('/aventureiros/{aventureiro}', [AventureiroController::class, 'show']);
        Route::get('/dungeons', [DungeonController::class, 'index']);
        Route::get('/dungeons/{dungeon}', [DungeonController::class, 'show']);
        Route::get('/quests', [QuestController::class, 'index']);
        Route::get('/quests/{quest}', [QuestController::class, 'show']);
        Route::post('/quests', [QuestController::class, 'store']);
        Route::post('/quests/registrar', [QuestController::class, 'registrar']);
        Route::post('/aventureiros/{aventureiro}/guilda', [AventureiroController::class, 'vincularGuilda']);
    });

    Route::prefix('admin')->middleware('role:admin')->group(function () {
        Route::post('/quests/registrar', [QuestController::class, 'registrar']);
        Route::post('/aventureiros/{aventureiro}/guilda', [AventureiroController::class, 'vincularGuilda']);
        Route::apiResource('guildas', GuildaController::class);
        Route::apiResource('aventureiros', AventureiroController::class);
        Route::apiResource('dungeons', DungeonController::class);
        Route::apiResource('quests', QuestController::class);
    });
});
