<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\RegistraAuditoria;

class Formulario extends Model {
    use RegistraAuditoria;
    protected $fillable = ['titulo', 'slug', 'descricao', 'status'];

    public function campos() {
        return $this->hasMany(CampoFormulario::class, 'formulario_id');
    }
    public function respostas() {
        return $this->hasMany(RespostaFormulario::class, 'formulario_id');
    }
}
