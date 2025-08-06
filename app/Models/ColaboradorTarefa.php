<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ColaboradorTarefa extends Model
{
    protected $table = 'colaborador_tarefa';
    protected $primaryKey = 'id';
    protected $fillable = ['colaborador_id', 'tarefa_id'];
    public $timestamps = false;
}
