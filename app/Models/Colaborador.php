<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Colaborador extends Model
{
    protected $table = 'colaboradores';
    protected $primaryKey = 'id';
    protected $fillable = [
        'primeiro_nome',
        'sobrenome',
        'nome_completo',
        'cpf',
        'email',
        'crated_at',
        'updated_at'
    ];

    public function tarefas()
    {
        return $this->belongsToMany(
            Tarefa::class,
            'colaborador_tarefa',
            'colaborador_id',
            'tarefa_id'
        )->withTimestamps();
    }
}
