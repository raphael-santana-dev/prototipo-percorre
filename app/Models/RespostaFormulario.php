<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\RegistraAuditoria;

class RespostaFormulario extends Model {
    use RegistraAuditoria;
    protected $fillable = ['formulario_id', 'user_id', 'respostas', 'etapa_parada'];
    protected $casts = ['respostas' => 'array'];
}
