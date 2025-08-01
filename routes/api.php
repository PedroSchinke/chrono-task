<?php

use App\Http\Controllers\ColaboradoresController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MaquinaController;
use App\Http\Controllers\TarefaController;
use App\Http\Controllers\HorarioDisponivelController;

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
Route::post('/tarefa/{tarefa_id}', [TarefaController::class, 'update']);
Route::post('/tarefa/{tarefa_id}/reposicionar', [TarefaController::class, 'reposicionarTarefa']);

/**
 * Colaboradores
 */
Route::get('/colaboradores', [ColaboradoresController::class, 'index']);
Route::post('/colaboradores', [ColaboradoresController::class, 'store']);

/**
 * Horários Disponíveis
 */
Route::get('/horarios-disponiveis', [HorarioDisponivelController::class, 'index']);
