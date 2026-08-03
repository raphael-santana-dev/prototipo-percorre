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

    // Isso faz o Laravel converter Arrays para JSON ao salvar, e JSON para Array ao ler
    protected $casts = [
        'informacao_anterior' => 'array',
        'nova_informacao' => 'array',
    ];

    public function usuario()
    {
        // Se a coluna no seu banco for 'user_id', use a linha abaixo:
        return $this->belongsTo(User::class, 'usuario_id'); 
    }
}