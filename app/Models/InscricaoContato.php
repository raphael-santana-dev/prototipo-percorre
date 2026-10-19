<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\RegistraAuditoria;

class InscricaoContato extends Model
{
    use RegistraAuditoria;

    protected $table = 'inscricao_contatos';
    protected $guarded = [];

    protected $casts = [
        'data_contato' => 'datetime',
        'houve_resposta' => 'boolean',
    ];

    public function inscricao()
    {
        return $this->belongsTo(Inscricao::class, 'inscricao_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}