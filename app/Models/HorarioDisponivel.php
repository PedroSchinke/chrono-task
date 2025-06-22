<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HorarioDisponivel extends Model
{
    protected $table = 'horarios_disponiveis';
    protected $primaryKey = 'id';
    protected $fillable = ['id_maquina', 'dia_semana',  'hora_inicio', 'hora_fim'];
    public $timestamps = false;

    public function maquina()
    {
        return $this->belongsTo(Maquina::class, 'id_maquina', 'id');
    }
}
