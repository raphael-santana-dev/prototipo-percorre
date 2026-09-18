<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\RegistraAuditoria;

class CicloAprendizagemFase extends Model
{
    use RegistraAuditoria;
    protected $table = 'ciclo_aprendizagem_fases';
    protected $guarded = ['id'];
    
    protected $casts = [
        'respondedores_permitidos' => 'array'
    ];

    public function ciclo()
    {
        return $this->belongsTo(CicloAprendizagem::class, 'ciclo_aprendizagem_id');
    }

    public function formularios()
    {
        return $this->hasMany(Formulario::class, 'ciclo_aprendizagem_fase_id');
    }
}