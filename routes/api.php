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
Route::post('/tarefa/{tarefa_id}/titulo/alterar', [TarefaController::class, 'alterarTitulo']);
Route::post('/tarefa/{tarefa_id}/descricao/alterar', [TarefaController::class, 'alterarDescricao']);
Route::post('/tarefa/{tarefa_id}/inicio/alterar', [TarefaController::class, 'alterarInicio']);
Route::post('/tarefa/{tarefa_id}/fim/alterar', [TarefaController::class, 'alterarFim']);
Route::post('/tarefa/{tarefa_id}/cor/alterar', [TarefaController::class, 'alterarCor']);

/**
 * Colaboradores
 */
Route::get('/colaboradores', [ColaboradorController::class, 'index'])->withoutMiddleware(['transaction']);
Route::post('/colaboradores', [ColaboradorController::class, 'store']);
