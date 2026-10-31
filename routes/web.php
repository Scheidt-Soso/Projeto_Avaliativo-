<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('jogador')->name('jogador.')->group(function () {

    Route::get('/dashboard', function () {
        return view('jogador.dashboard');
    })->name('dashboard');

});

Route::prefix('admin')->name('admin.')->group(function () {

    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('dashboard');

});