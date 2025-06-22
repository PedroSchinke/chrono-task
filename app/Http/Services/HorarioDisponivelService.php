<?php

namespace App\Http\Services;

use App\Models\HorarioDisponivel;
use Carbon\Carbon;

class HorarioDisponivelService
{
    public function criarHorariosDisponiveis($maquina, $horarios)
    {
        $horariosToInsert = [];


        foreach ($horarios as $horario) {
            $horariosToInsert[] = [
                'id_maquina' => $maquina->id,
                'dia_semana'  => $horario['dia_semana'],
                'hora_inicio' => Carbon::parse($horario['hora_inicio'])->setTimezone('America/Sao_Paulo')->format('H:i'),
                'hora_fim'    => Carbon::parse($horario['hora_fim'])->setTimezone('America/Sao_Paulo')->format('H:i'),
            ];
        }

        HorarioDisponivel::insert($horariosToInsert);
    }
}
