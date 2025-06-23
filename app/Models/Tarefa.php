<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tarefa extends Model
{
    protected $table = 'tarefas';
    protected $primaryKey = 'id';
    protected $fillable = [
        'titulo',
        'descricao',
        'id_maquina',
        'inicio',
        'fim',
        'cor',
        'periodo_diario_inicio',
        'periodo_diario_fim'
    ];
    public $timestamps = false;

    public function maquina()
    {
        return $this->belongsTo(Maquina::class, 'id_maquina', 'id');
    }
}
