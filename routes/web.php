<?php

use App\Http\Controllers\Web\AventureiroController;
use App\Http\Controllers\Web\GuildDashboardController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\LoginController;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:30,1')->name('login.store');
    Route::get('/register', [LoginController::class, 'showRegister'])->name('register');
    Route::post('/register', [LoginController::class, 'register'])->middleware('throttle:6,1')->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/', [GuildDashboardController::class, 'index'])->name('guildas.dashboard');
    Route::post('/guildas/{guilda}/expedicao/selecionar', [GuildDashboardController::class, 'selecionarDestino'])->name('guildas.expedicao.selecionar');
    Route::get('/aventureiros', [AventureiroController::class, 'index'])->name('aventureiros.escolher');
    Route::post('/aventureiro/selecionar', [AventureiroController::class, 'selecionar'])->name('aventureiros.selecionar');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::redirect('/jogador/dashboard', '/')->name('jogador.dashboard');
    Route::redirect('/admin/dashboard', '/')->middleware('can:gerenciar-cadastros')->name('admin.dashboard');
});
