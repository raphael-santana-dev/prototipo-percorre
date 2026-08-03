<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditoriaLog extends Model
{
    protected $guarded = [];

    // Converte automaticamente de JSON para Array quando formos ler os dados
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