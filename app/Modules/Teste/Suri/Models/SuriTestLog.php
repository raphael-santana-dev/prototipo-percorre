<?php

namespace App\Modules\Teste\Suri\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class SuriTestLog extends Model
{
    protected $table = 'suri_test_logs';

    protected $fillable = [
        'acao',
        'metodo',
        'url',
        'http_status',
        'sucesso',
        'payload',
        'resposta',
        'user_id',
        'user_nome',
        'ip',
    ];

    protected $casts = [
        'sucesso'     => 'boolean',
        'http_status' => 'integer',
        'payload'     => 'array',
        'resposta'    => 'array',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}