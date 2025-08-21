<?php

namespace App\Http\Controllers;

use App\Models\Colaborador;
use App\Models\Maquina;
use Carbon\Carbon;
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
        $query = Colaborador::with('tarefas');

        if (!empty($request->get('id'))) {
            $query->where('id', $request->get('id'));
        }

        if (!empty($request->get('nome_completo'))) {
            $query->where('nome_completo', 'like', '%' . $request->get('nome_completo') . '%');
        }

        if (!empty($request->get('cpf'))) {
            $query->where('cpf', 'like', '%' . $request->get('cpf') . '%');
        }

        $sortField = 'nome_completo';
        $sortOrder = 'asc';

        if (!empty($request->get('sort_field'))) {
            $sortField = $request->get('sort_field');
        }

        if (!empty($request->get('sort_order'))) {
            $sortOrder = $request->get('sort_order');
        }

        $query->orderBy($sortField, $sortOrder);

        $colaboradores = $query->paginate($request->get('per_page', 10));

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
            'email' => $request->get('email'),
            'created_at' => Carbon::now('America/Sao_paulo'),
            'updated_at' => Carbon::now('America/Sao_paulo')
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
