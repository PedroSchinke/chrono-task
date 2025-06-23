<?php

namespace App\Http\Services;

use App\Http\Helpers\DateHelper;
use App\Models\HorarioDisponivel;

class HorarioDisponivelService
{
    /**
     * @param $maquina
     * @param $horarios
     * @return void
     */
    public function criarHorariosDisponiveis($maquina, $horarios)
    {
        $horariosToInsert = [];


        foreach ($horarios as $horario) {
            $horariosToInsert[] = [
                'id_maquina' => $maquina->id,
                'dia_semana'  => $horario['dia_semana'],
                'hora_inicio' => DateHelper::formatarData($horario['hora_inicio'], 'H:i'),
                'hora_fim'    => DateHelper::formatarData($horario['hora_fim'], 'H:i'),
            ];
        }

        HorarioDisponivel::insert($horariosToInsert);
    }
}
