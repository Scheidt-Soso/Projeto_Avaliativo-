<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\LoginController;

Route::get('/', function () {
    return view('welcome');
});

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

    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('dashboard');

});