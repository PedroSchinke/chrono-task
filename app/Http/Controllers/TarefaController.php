<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Helpers\DateHelper;
use App\Http\Services\HorarioDisponivelService;
use App\Models\HorarioDisponivel;
use App\Models\Maquina;
use App\Models\Tarefa;
use Exception;
use Illuminate\Http\Request;

class TarefaController extends Controller
{
    private HorarioDisponivelService $horariosService;

    public function __construct()
    {
        $this->horariosService = new HorarioDisponivelService();
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
            'descricao' => 'required|string',
            'id_maquina' => 'required|integer',
            'inicio' => 'required|string',
            'fim' => 'required|string',
            'cor' => 'required|string',
            'periodo_diario_inicio' => 'required|string',
            'periodo_diario_fim' => 'required|string',
        ]);

        $horaInicio = DateHelper::formatarData($request->get('periodo_diario_inicio'), 'H:i');
        $horaFim = DateHelper::formatarData($request->get('periodo_diario_fim'), 'H:i');
        $diasDaSemana = DateHelper::getDiasDaSemana($request->get('inicio'), $request->get('fim'));

        foreach ($diasDaSemana as $dia) {
            $horarioDisponivel = HorarioDisponivel::query()
                ->where('id_maquina', $request->get('id_maquina'))
                ->where('hora_inicio',  $horaInicio)
                ->where('hora_fim',  $horaFim)
                ->where('dia_semana', $dia)
                ->exists();

            if (!$horarioDisponivel) {
                throw new Exception('Horários indisponíveis para o período');
            }
        }

        $inicio = DateHelper::formatarData($request->get('inicio'));
        $fim = DateHelper::formatarData($request->get('fim'));

        $tarefa = Tarefa::create([
            'titulo' => $request->get('titulo'),
            'descricao' => $request->get('descricao'),
            'id_maquina' => $request->get('id_maquina'),
            'inicio' => $inicio,
            'fim' => $fim,
            'cor' => $request->get('cor'),
            'periodo_diario_inicio' => $horaInicio,
            'periodo_diario_fim' => $horaFim
        ]);

        return response()->json([
            'mensagem' => 'Tarefa criada com sucesso!',
            'tarefa' => $tarefa
        ], 201);
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
    public function update(Request $request, Maquina $maquina)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Maquina $maquina)
    {
        //
    }
}
