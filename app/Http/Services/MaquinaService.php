<?php

namespace App\Http\Services;

use App\Models\Maquina;
use Carbon\Carbon;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MaquinaService
{
    /**
     * @param $maquinaId
     * @param $inicio
     * @param $fim
     * @return bool
     * @throws NotFoundHttpException
     */
    public function verificarDisponibilidade($maquinaId, $inicio, $fim)
    {
        $maquinaModel = Maquina::with('tarefas')->find($maquinaId);

        if (!$maquinaModel) {
            throw new NotFoundHttpException("Máquina de ID {$maquinaId} não encontrado.");
        }

        $inicio = Carbon::parse($inicio);
        $fim = Carbon::parse($fim);

        $jaPossuiTarefaNoPeriodo = $maquinaModel->tarefas()
            ->where('inicio', '<', $fim)
            ->where('fim', '>', $inicio)
            ->exists();

        if ($jaPossuiTarefaNoPeriodo) {
            return false;
        }

        return true;
    }
}

