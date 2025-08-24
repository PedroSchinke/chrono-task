<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Helpers\DateHelper;
use App\Http\Services\TarefaService;
use App\Models\Tarefa;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TarefaController extends Controller
{
    private TarefaService $tarefaService;

    public function __construct()
    {
        $this->tarefaService = new TarefaService();
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $tarefas = Tarefa::with(['colaboradores', 'maquinas'])->get();

        return response()->json($tarefas);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'titulo' => 'required|string',
            'inicio' => 'required|date',
            'fim' => 'required|date',
            'cor' => 'required|string',
        ]);

        $tarefa = $this->tarefaService->criarTarefa($request->all());

        return response()->json(['mensagem' => 'Tarefa criada com sucesso!', 'tarefa' => $tarefa], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show()
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit()
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param $tarefa_id
     * @return JsonResponse
     */
    public function update(Request $request, $tarefa_id): JsonResponse
    {
        $tarefa = Tarefa::find($tarefa_id);

        $inicio = DateHelper::formatarData($request->get('inicio'));
        $fim = DateHelper::formatarData($request->get('fim'));

        $tarefa->update([
            'titulo' => $request->get('titulo'),
            'descricao' => $request->get('descricao'),
            'id_maquina' => $request->get('id_maquina'),
            'inicio' => $inicio,
            'fim' => $fim,
            'periodo_diario_inicio' => $request->get('periodo_diario_inicio'),
            'periodo_diario_fim' => $request->get('periodo_diario_fim'),
            'cor' => $request->get('cor')
        ]);

        return response()->json(['mensagem' => 'Tarefa movida com sucesso!'], 204);
    }

    /**
     * @param Request $request
     * @param $tarefa_id
     * @return JsonResponse
     */
    public function reposicionarTarefa(Request $request, $tarefa_id): JsonResponse
    {
        $request->validate([
            'inicio' => 'required|string',
            'fim' => 'required|string',
        ]);

        $tarefa = Tarefa::find($tarefa_id);

        $tarefa->update([
            'inicio' => $request->get('inicio'),
            'fim' => $request->get('fim'),
            'id_maquina' => $request->get('id_maquina')
        ]);

        return response()->json(['mensagem' => 'Tarefa movida com sucesso!']);
    }

    /**
     * @param Request $request
     * @param $tarefa_id
     * @return JsonResponse
     */
    public function alterarTitulo(Request $request, $tarefa_id): JsonResponse
    {
        $request->validate([
            'titulo' => 'required|string'
        ]);

        $tarefa = Tarefa::find($tarefa_id);

        $tarefa->update([
            'titulo' => $request->get('titulo')
        ]);

        return response()->json(['mensagem' => 'Título da tarefa alterado com sucesso!']);
    }

    /**
     * @param Request $request
     * @param $tarefa_id
     * @return JsonResponse
     */
    public function alterarDescricao(Request $request, $tarefa_id): JsonResponse
    {
        $tarefa = Tarefa::find($tarefa_id);

        $tarefa->update([
            'descricao' => $request->get('descricao')
        ]);

        return response()->json(['mensagem' => 'Descrição da tarefa alterada com sucesso!']);
    }

    /**
     * @param Request $request
     * @param $tarefa_id
     * @return JsonResponse
     */
    public function alterarInicio(Request $request, $tarefa_id): JsonResponse
    {
        $request->validate([
            'inicio' => 'required|date'
        ]);

        $tarefa = Tarefa::find($tarefa_id);

        $tarefa->update([
            'inicio' => DateHelper::formatarData($request->get('inicio'))
        ]);

        return response()->json(['mensagem' => 'Início da tarefa alterado com sucesso!']);
    }

    /**
     * @param Request $request
     * @param $tarefa_id
     * @return JsonResponse
     */
    public function alterarFim(Request $request, $tarefa_id): JsonResponse
    {
        $request->validate([
            'fim' => 'required|date'
        ]);

        $tarefa = Tarefa::find($tarefa_id);

        $tarefa->update([
            'fim' => DateHelper::formatarData($request->get('fim'))
        ]);

        return response()->json(['mensagem' => 'Fim da tarefa alterado com sucesso!']);
    }

    /**
     * @param Request $request
     * @param $tarefa_id
     * @return JsonResponse
     */
    public function alterarCor(Request $request, $tarefa_id): JsonResponse
    {
        $request->validate([
            'cor' => 'required|string'
        ]);

        $tarefa = Tarefa::find($tarefa_id);

        $tarefa->update([
            'cor' => $request->get('cor')
        ]);

        return response()->json(['mensagem' => 'Cor da tarefa alterada com sucesso!']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Request $request
     * @param $tarefa_id
     * @return JsonResponse
     * @throws Exception
     */
    public function destroy(Request $request, $tarefa_id): JsonResponse
    {
        $tarefa = Tarefa::find($tarefa_id);

        if (!$tarefa) {
            throw new Exception("Não foi possível encontrar tarefa de id {$tarefa_id}.");
        }

        $tarefa->delete();

        return response()->json(['mensagem' => 'Tarefa excluída com sucesso!'], 204);
    }
}
