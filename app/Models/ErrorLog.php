<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ErrorLog extends Model
{
    protected $fillable = [
        'tipo', 'http_code', 'mensagem', 'arquivo', 'linha',
        'url', 'metodo_http', 'user_id', 'stack_trace', 'resolvido'
    ];

    protected $casts = [
        'resolvido' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}