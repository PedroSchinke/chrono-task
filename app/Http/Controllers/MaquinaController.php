<?php

namespace App\Http\Controllers;

use App\Http\Helpers\DateHelper;
use App\Models\Maquina;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaquinaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Maquina::query();

        if (!empty($request->get('id'))) {
            $query->where('id', $request->get('id'));
        }

        if (!empty($request->get('nome'))) {
            $query->where('nome', 'like', '%' . $request->get('nome') . '%');
        }

        if (!empty($request->get('descricao'))) {
            $query->where('descricao', 'like', '%' . $request->get('descricao') . '%');
        }

        $sortField = 'nome';
        $sortOrder = 'asc';

        if (!empty($request->get('sort_field'))) {
            $sortField = $request->get('sort_field');
        }

        if (!empty($request->get('sort_order'))) {
            $sortOrder = $request->get('sort_order');
        }

        $query->orderBy($sortField, $sortOrder);

        return response()->json(['data' => $query->paginate($request->get('per_page', 50))]);
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

        Maquina::create([
            'nome' => $request->get('nome'),
            'descricao' => $request->get('descricao', '')
        ]);

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
