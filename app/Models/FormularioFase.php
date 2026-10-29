<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\RegistraAuditoria;

class FormularioFase extends Model
{
    use RegistraAuditoria;

    protected $table = 'formulario_fases';

    protected $fillable = [
        'formulario_id', 'ciclo_id', 'nome', 'paginas', 
        'data_liberacao', 'data_encerramento', 
        'email_template_id', 'notificacao_enviada',
    ];

    protected $casts = [
        'paginas' => 'array',
        'data_liberacao' => 'datetime',
        'data_encerramento' => 'datetime',
        'notificacao_enviada' => 'boolean',
    ];

    public function formulario() { return $this->belongsTo(Formulario::class); }
    public function ciclo() { return $this->belongsTo(Ciclo::class); }
    public function templateEmail() { return $this->belongsTo(\App\Modules\Comunicacao\Domain\Models\EmailTemplate::class, 'email_template_id'); }
}