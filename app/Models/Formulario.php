<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Formulario extends Model {
    protected $fillable = ['titulo', 'slug', 'descricao', 'status'];

    public function campos() {
        return $this->hasMany(CampoFormulario::class, 'formulario_id');
    }
    public function respostas() {
        return $this->hasMany(RespostaFormulario::class, 'formulario_id');
    }
}
