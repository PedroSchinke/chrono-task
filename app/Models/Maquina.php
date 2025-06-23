<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Maquina extends Model
{
    protected $table = 'maquinas';
    protected $primaryKey = 'id';
    protected $fillable = ['nome'];
    public $timestamps = false;

    public function horariosDisponiveis()
    {
        return $this->hasMany(HorarioDisponivel::class, 'id_maquina', 'id');
    }

    public function tarefas()
    {
        return $this->hasMany(Tarefa::class, 'id_maquina', 'id');
    }
}
