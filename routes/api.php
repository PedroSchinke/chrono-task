<?php

use App\Http\Controllers\MaquinaController;
use App\Http\Controllers\TarefaController;
use Illuminate\Support\Facades\Route;

/**
 * Máquinas
 */
Route::get('/maquinas', [MaquinaController::class, 'index']);
Route::post('/maquina', [MaquinaController::class, 'store']);

/**
 * Tarefas
 */
Route::get('/tarefas', [TarefaController::class, 'index']);
Route::post('/tarefa', [TarefaController::class, 'store']);


