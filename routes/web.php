<?php

use App\Http\Controllers\Web\AventureiroController;
use App\Http\Controllers\Web\GuildDashboardController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\LoginController;

foreach (['registro', 'guilda', 'aventureiro', 'dungeon', 'quest'] as $parametro) Route::pattern($parametro, '[0-9]+');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:30,1')->name('login.store');
    Route::get('/register', [LoginController::class, 'showRegister'])->name('register');
    Route::post('/register', [LoginController::class, 'register'])->middleware('throttle:6,1')->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/perfil', [\App\Http\Controllers\Web\PerfilController::class, 'show'])->name('perfil.show');
    Route::get('/relatorios', [\App\Http\Controllers\Web\RelatorioController::class, 'index'])->name('relatorios.index');
    Route::post('/relatorios/atualizar', [\App\Http\Controllers\Web\RelatorioController::class, 'atualizar'])->middleware('can:gerenciar-cadastros')->name('relatorios.atualizar');
    Route::post('/perfil/guilda', [\App\Http\Controllers\Web\PerfilController::class, 'vincularGuilda'])->name('perfil.guilda');
    Route::post('/perfil/quests', [\App\Http\Controllers\Web\PerfilController::class, 'registrarQuest'])->name('perfil.quests');
    Route::prefix('cadastros')->name('cadastros.')->group(function () {
        foreach ([
            'guildas' => \App\Http\Controllers\Web\GuildaController::class,
            'aventureiros' => \App\Http\Controllers\Web\AventureiroCadastroController::class,
            'dungeons' => \App\Http\Controllers\Web\DungeonController::class,
            'quests' => \App\Http\Controllers\Web\QuestController::class,
        ] as $recurso => $controller) {
            Route::resource($recurso, $controller)->parameters([$recurso => 'registro'])
                ->middlewareFor(['create', 'store', 'edit', 'update', 'destroy'], 'can:gerenciar-cadastros');
        }
    });
    Route::get('/', [GuildDashboardController::class, 'index'])->name('guildas.dashboard');
    Route::post('/guildas/{guilda}/expedicao/selecionar', [GuildDashboardController::class, 'selecionarDestino'])->name('guildas.expedicao.selecionar');
    Route::get('/aventureiros', [AventureiroController::class, 'index'])->name('aventureiros.escolher');
    Route::post('/aventureiro/selecionar', [AventureiroController::class, 'selecionar'])->name('aventureiros.selecionar');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::redirect('/jogador/dashboard', '/')->name('jogador.dashboard');
    Route::redirect('/admin/dashboard', '/')->middleware('can:gerenciar-cadastros')->name('admin.dashboard');
});
