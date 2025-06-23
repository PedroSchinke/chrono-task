<?php

namespace App\Http\Helpers;

use Carbon\Carbon;
use Carbon\CarbonPeriod;

class DateHelper
{
    /**
     * @param $dataInicio
     * @param $dataFim
     * @return array
     */
    public static function getDiasDaSemana($dataInicio, $dataFim): array
    {
        $periodo = CarbonPeriod::create($dataInicio, $dataFim);

        $diasSemana = collect($periodo)
            ->map(function ($date) {
                $nome = explode('-', strtolower($date->locale('pt_BR')->dayName))[0];
                return str_replace(['á', 'ç'], ['a', 'c'], $nome);
            })
            ->unique()
            ->toArray();

        return  $diasSemana;
    }

    /**
     * @param $data
     * @param $formato
     * @return Carbon|string
     */
    public static function formatarData($data, $formato = null)
    {
        $data = Carbon::parse($data)->setTimezone('America/Sao_Paulo');

        if ($formato) {
            $data = $data->format($formato);
        }

        return $data;
    }
}
