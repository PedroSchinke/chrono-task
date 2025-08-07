<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaquinaTarefa extends Model
{
    protected $table = 'maquina_tarefa';
    protected $primaryKey = 'id';
    protected $fillable = ['maquina_id', 'tarefa_id'];
    public $timestamps = false;
}
