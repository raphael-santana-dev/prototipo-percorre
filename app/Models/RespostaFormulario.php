<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RespostaFormulario extends Model {
    protected $fillable = ['formulario_id', 'user_id', 'respostas', 'etapa_parada'];
    protected $casts = ['respostas' => 'array'];
}
