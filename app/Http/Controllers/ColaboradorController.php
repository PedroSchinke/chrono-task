<?php

namespace App\Http\Controllers;

use App\Models\Colaborador;
use App\Models\Maquina;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ColaboradorController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $colaboradores = Colaborador::with('tarefas')->get();

        return response()->json(['data' => $colaboradores]);
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
            'primeiro_nome' => 'required|string',
            'sobrenome' => 'required|string',
            'cpf' => 'required|integer|unique:colaboradores',
            'email' => 'required|string|email|unique:colaboradores',
        ]);

        Colaborador::create([
            'primeiro_nome' => $request->get('primeiro_nome'),
            'sobrenome' => $request->get('sobrenome'),
            'nome_completo' => $request->get('primeiro_nome') . ' ' . $request->get('sobrenome'),
            'cpf' => $request->get('cpf'),
            'email' => $request->get('email')
        ]);

        return response()->json(['message' => 'Colaborador adicionado com sucesso!'], 201);
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
