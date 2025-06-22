<?php

namespace App\Http\Controllers;

use App\Http\Services\HorarioDisponivelService;
use App\Models\Maquina;
use App\Http\Controllers\Controller;
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
     */
    public function index()
    {
        $maquinas = Maquina::with(['horariosDisponiveis'])->get();

        return response()->json([
            'data' => $maquinas,
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
