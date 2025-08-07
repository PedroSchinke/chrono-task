<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tarefa extends Model
{
    protected $table = 'tarefas';
    protected $primaryKey = 'id';
    protected $fillable = ['titulo', 'descricao', 'inicio', 'fim', 'cor'];
    public $timestamps = false;

    public function colaboradores()
    {
        return $this->belongsToMany(Colaborador::class, 'colaborador_tarefa', 'tarefa_id', 'colaborador_id');
    }

    public function maquinas()
    {
        return $this->belongsToMany(Maquina::class, 'maquina_tarefa', 'tarefa_id', 'maquina_id');
    }
}
