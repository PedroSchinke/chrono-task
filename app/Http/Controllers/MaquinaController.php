<?php

namespace App\Http\Controllers;

use App\Http\Helpers\DateHelper;
use App\Http\Services\HorarioDisponivelService;
use App\Models\Maquina;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MaquinaController extends Controller
{
    private HorarioDisponivelService $horariosService;

    public function __construct()
    {
        $this->horariosService = new HorarioDisponivelService();
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $maquinas = Maquina::query();

        if ($request->filled('nome')) {
            $maquinas->where('nome', 'like', '%' . $request->input('nome') . '%');
        }

        $temFiltroDeData = $request->filled('data_inicio') &&
            $request->filled('data_fim') &&
            $request->filled('periodo_diario_inicio') &&
            $request->filled('periodo_diario_fim');

        if ($temFiltroDeData) {
            $dataInicio = DateHelper::formatarData($request->input('data_inicio'));
            $dataFim = DateHelper::formatarData($request->input('data_fim'));
            $horaInicio = DateHelper::formatarData($request->input('periodo_diario_inicio'), 'H:i');
            $horaFim = DateHelper::formatarData($request->input('periodo_diario_fim'), 'H:i');

            $diasSemana = DateHelper::getDiasDaSemana($dataInicio, $dataFim);

            $quantidadeDias = count($diasSemana);

            $maquinas->whereHas('horariosDisponiveis', function ($query) use ($diasSemana, $horaInicio, $horaFim) {
                $query->whereIn('dia_semana', $diasSemana)
                      ->whereTime('hora_inicio', '<=', $horaInicio)
                      ->whereTime('hora_fim', '>=', $horaFim);
            }, '=', $quantidadeDias);
        } else {
            $maquinas->with('horariosDisponiveis');
        }

        return response()->json([
            'data' => $maquinas->get(),
        ]);
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
    public function store(Request $request)
    {
        $request->validate([
            'nome' => 'required|string',
        ]);

        DB::transaction(function () use ($request) {
            $maquina = Maquina::create([
                'nome' => $request->get('nome')
            ]);

            $this->horariosService->criarHorariosDisponiveis($maquina, $request->get('dias_horarios'));
        });

        return response()->json(['mensagem' => 'Máquina criada com sucesso!'], 201);
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
