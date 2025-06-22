<?php

use App\Http\Controllers\MaquinaController;
use Illuminate\Support\Facades\Route;

Route::post('/maquina', [MaquinaController::class, 'store']);
Route::get('/maquinas', [MaquinaController::class, 'index']);

