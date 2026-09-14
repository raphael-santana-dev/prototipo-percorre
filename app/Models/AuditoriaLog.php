<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditoriaLog extends Model
{
    protected $table = 'auditoria_logs';

    protected $fillable = [
        'tabela_alterada',
        'registro_id',
        'acao',
        'informacao_anterior',
        'nova_informacao',
        'usuario_id',
        'usuario_nome',
        'usuario_role',
        'usuario_login',
        'ip',
        'navegador',
    ];

    protected $casts = [
        'informacao_anterior' => 'array',
        'nova_informacao' => 'array',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id'); 
    }
}