<?php

use App\Http\Controllers\Web\AventureiroController;
use App\Http\Controllers\Web\GuildDashboardController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\LoginController;

Route::get('/', [GuildDashboardController::class, 'index'])->name('guildas.dashboard');
Route::post('/guildas/{guilda}/expedicao/sortear', [GuildDashboardController::class, 'sortear'])->name('guildas.sortear');
Route::get('/aventureiros', [AventureiroController::class, 'index'])->name('aventureiros.escolher');
Route::post('/aventureiro/selecionar', [AventureiroController::class, 'selecionar'])->name('aventureiros.selecionar');

// Rotas públicas
Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.store');

Route::get('/register', [LoginController::class, 'showRegister'])->name('register');
Route::post('/register', [LoginController::class, 'register'])->name('register.store');

Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Rotas do jogador
Route::prefix('jogador')->name('jogador.')->group(function () {

    Route::middleware(['auth', 'role:jogador'])->group(function () {

        Route::get('/dashboard', function () {
            return view('jogador.dashboard');
        })->name('dashboard');

    });

});

Route::prefix('admin')->name('admin.')->group(function () {

    Route::middleware(['auth', 'role:admin'])->group(function () {

        Route::get('/dashboard', function () {
            return view('admin.dashboard');
        })->name('dashboard');

    });

});
