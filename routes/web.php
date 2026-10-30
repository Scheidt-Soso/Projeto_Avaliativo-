<?php

use App\Http\Controllers\Web\AventureiroController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AventureiroController::class, 'index'])->name('aventureiros.escolher');
Route::post('/aventureiro/selecionar', [AventureiroController::class, 'selecionar'])->name('aventureiros.selecionar');
