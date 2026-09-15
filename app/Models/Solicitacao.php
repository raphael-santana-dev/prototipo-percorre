<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\RegistraAuditoria;

class Solicitacao extends Model
{
    use RegistraAuditoria;

    protected $table = 'solicitacoes';
    protected $fillable = ['tema', 'solicitante_type', 'solicitante_id', 'responsavel_id', 'justificativa', 'resposta_admin', 'status', 'payload'];
    protected $casts = ['payload' => 'array'];

    public function solicitante() {
        return $this->morphTo();
    }

    public function responsavel() {
        return $this->belongsTo(User::class, 'responsavel_id');
    }
}