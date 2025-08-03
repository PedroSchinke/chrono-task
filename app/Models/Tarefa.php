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
    ];
    public $timestamps = false;

    public function colaboradores()
    {
        return $this->belongsTo(Colaborador::class);
    }

    public function maquinas()
    {
        return $this->belongsTo(Maquina::class);
    }
}
