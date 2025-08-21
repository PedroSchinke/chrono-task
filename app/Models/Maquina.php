<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Maquina extends Model
{
    protected $table = 'maquinas';
    protected $primaryKey = 'id';
    protected $fillable = ['nome', 'descricao'];
    public $timestamps = true;

    public function tarefas()
    {
        return $this->belongsToMany(
            Tarefa::class,
            'maquina_tarefa',
            'maquina_id',
            'tarefa_id'
        )->withTimestamps();
    }
}
