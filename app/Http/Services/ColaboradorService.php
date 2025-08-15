<?php

namespace App\Http\Services;

use App\Models\Colaborador;
use Carbon\Carbon;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ColaboradorService
{
    /**
     * @param $colaboradorId
     * @param $inicio
     * @param $fim
     * @return bool
     * @throws NotFoundHttpException
     */
    public function verificarDisponibilidade($colaboradorId, $inicio, $fim)
    {
        $colaboradorModel = Colaborador::with('tarefas')->find($colaboradorId);

        if (!$colaboradorModel) {
            throw new NotFoundHttpException("Colaborador de ID {$colaboradorId} não encontrado.");
        }

        $inicio = Carbon::parse($inicio);
        $fim = Carbon::parse($fim);

        $jaPossuiTarefaNoPeriodo = $colaboradorModel->tarefas()
            ->where('inicio', '<', $fim)
            ->where('fim', '>', $inicio)
            ->exists();

        if ($jaPossuiTarefaNoPeriodo) {
            return false;
        }

        return true;
    }
}
