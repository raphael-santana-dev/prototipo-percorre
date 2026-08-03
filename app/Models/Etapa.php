<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\RegistraAuditoria;

class Etapa extends Model
{
    use RegistraAuditoria;
    protected $fillable = [
        'numero',
        'nome',
        'descricao'
    ];
}
