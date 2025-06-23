<?php

namespace App\Http\Controllers;

use App\Http\Helpers\DateHelper;
use App\Http\Services\HorarioDisponivelService;
use App\Models\HorarioDisponivel;
use App\Models\Maquina;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HorarioDisponivelController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $horariosDisponiveis = HorarioDisponivel::with('maquina')->get();

        return response()->json([
            'data' => $horariosDisponiveis,
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
        //
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
