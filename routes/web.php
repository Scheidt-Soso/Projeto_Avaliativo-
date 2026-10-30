<?php

use App\Http\Controllers\Web\AventureiroController;
use App\Http\Controllers\Web\GuildDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [GuildDashboardController::class, 'index'])->name('guildas.dashboard');
Route::post('/guildas/{guilda}/expedicao/sortear', [GuildDashboardController::class, 'sortear'])->name('guildas.sortear');
Route::get('/aventureiros', [AventureiroController::class, 'index'])->name('aventureiros.escolher');
Route::post('/aventureiro/selecionar', [AventureiroController::class, 'selecionar'])->name('aventureiros.selecionar');
