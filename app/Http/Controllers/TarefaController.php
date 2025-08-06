<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Helpers\DateHelper;
use App\Http\Services\TarefaService;
use App\Models\Colaborador;
use App\Models\ColaboradorTarefa;
use App\Models\Maquina;
use App\Models\Tarefa;
use Carbon\Carbon;
use Exception;
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
     */
    public function index(Request $request)
    {
        $tarefas = Tarefa::with(['maquina'])->get();

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
     * @throws Exception
     */
    public function store(Request $request)
    {
        $request->validate([
            'titulo' => 'required|string',
            'inicio' => 'required|date',
            'fim' => 'required|date',
            'cor' => 'required|string',
        ]);

        $inicio = Carbon::parse($request->get('inicio'));
        $fim = Carbon::parse($request->get('fim'));

        $colaboradores = $request->get('colaboradores', []);

        if (!empty($colaboradores)) {
            foreach ($colaboradores as $colaborador) {
                $colaboradorModel = Colaborador::with('tarefas')->find($colaborador['id']);

                if (!$colaboradorModel) {
                    return response()->json(['erro' => "Colaborador de ID {$colaborador['id']} não encontrado."], 404);
                }

                $jaPossuiTarefaNoPeriodo = $colaboradorModel->tarefas()
                    ->where('inicio', '<', $fim)
                    ->where('fim', '>', $inicio)
                    ->exists();

                if ($jaPossuiTarefaNoPeriodo) {
                    return response()->json([
                        'erro' => "O colaborador {$colaboradorModel->nome_completo} já possui tarefa no período informado."
                    ], 422);
                }
            }
        }

        $inicio = DateHelper::formatarData($request->get('inicio'));
        $fim = DateHelper::formatarData($request->get('fim'));

        $tarefa = Tarefa::create([
            'titulo' => $request->get('titulo'),
            'descricao' => $request->get('descricao'),
            'inicio' => $inicio,
            'fim' => $fim,
            'cor' => $request->get('cor'),
        ]);

        if (!empty($colaboradores)) {
            foreach ($colaboradores as $colaborador) {
                ColaboradorTarefa::create([
                    'colaborador_id' => $colaborador['id'],
                    'tarefa_id' => $tarefa->id,
                ]);
            }
        }

        return response()->json(['mensagem' => 'Tarefa criada com sucesso!', 'tarefa' => $tarefa], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Maquina $maquina)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Maquina $maquina)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $tarefa_id)
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

    public function reposicionarTarefa(Request $request, $tarefa_id)
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

        return response()->json(['mensagem' => 'Tarefa movida com sucesso!'], 204);
    }

    public function alterarCor(Request $request, $tarefa_id)
    {
        $request->validate([
            'cor' => 'required|string'
        ]);

        $tarefa = Tarefa::find($tarefa_id);

        $tarefa->update([
            'cor' => $request->get('cor')
        ]);

        return response()->json(['mensagem' => 'Cor da tarefa alterada com sucesso!'], 204);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Maquina $maquina)
    {
        //
    }
}
