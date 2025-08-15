<?php

use App\Http\Controllers\ColaboradorController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MaquinaController;
use App\Http\Controllers\TarefaController;

/**
 * Máquinas
 */
Route::get('/maquinas', [MaquinaController::class, 'index'])->withoutMiddleware(['transaction']);
Route::post('/maquina', [MaquinaController::class, 'store']);

/**
 * Tarefas
 */
Route::get('/tarefas', [TarefaController::class, 'index'])->withoutMiddleware(['transaction']);
Route::post('/tarefa', [TarefaController::class, 'store']);
Route::post('/tarefa/{tarefa_id}', [TarefaController::class, 'update']);
Route::post('/tarefa/deletar/{tarefa_id}', [TarefaController::class, 'destroy']);
Route::post('/tarefa/{tarefa_id}/reposicionar', [TarefaController::class, 'reposicionarTarefa']);

/**
 * Colaboradores
 */
Route::get('/colaboradores', [ColaboradorController::class, 'index'])->withoutMiddleware(['transaction']);
Route::post('/colaboradores', [ColaboradorController::class, 'store']);
