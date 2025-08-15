<?php

namespace App\Http\Services;

use App\Exceptions\IndisponibilidadeException;
use App\Http\Helpers\DateHelper;
use App\Models\ColaboradorTarefa;
use App\Models\MaquinaTarefa;
use App\Models\Tarefa;

class TarefaService
{
    private ColaboradorService $colaboradorService;
    private MaquinaService $maquinaService;

    public function __construct()
    {
        $this->colaboradorService = new ColaboradorService();
        $this->maquinaService = new MaquinaService();
    }

    /**
     * @param $params
     * @return mixed
     */
    public function criarTarefa($params)
    {
        $inicio = $params['inicio'];
        $fim = $params['fim'];

        $colaboradores = $params['colaboradores'] ?? [];

        foreach ($colaboradores as $colaborador) {
            $disponivel = $this->colaboradorService->verificarDisponibilidade($colaborador['id'], $inicio, $fim);

            if (!$disponivel) {
                throw new IndisponibilidadeException($colaborador['nome_completo']);
            }
        }

        $maquinas = $params['maquinas'] ?? [];

        foreach ($maquinas as $maquina) {
            $disponivel = $this->maquinaService->verificarDisponibilidade($maquina['id'], $inicio, $fim);

            if (!$disponivel) {
                throw new IndisponibilidadeException($maquina['nome']);
            }
        }

        $inicio = DateHelper::formatarData($params['inicio']);
        $fim = DateHelper::formatarData($params['fim']);

        $tarefa = Tarefa::create([
            'titulo' => $params['titulo'],
            'descricao' => $params['descricao'],
            'inicio' => $inicio,
            'fim' => $fim,
            'cor' => $params['cor'],
        ]);

        if (!empty($colaboradores)) {
            foreach ($colaboradores as $colaborador) {
                ColaboradorTarefa::create([
                    'colaborador_id' => $colaborador['id'],
                    'tarefa_id' => $tarefa->id,
                ]);
            }
        }

        if (!empty($maquinas)) {
            foreach ($maquinas as $maquina) {
                MaquinaTarefa::create([
                    'maquina_id' => $maquina['id'],
                    'tarefa_id' => $tarefa->id,
                ]);
            }
        }

        return $tarefa;
    }
}
