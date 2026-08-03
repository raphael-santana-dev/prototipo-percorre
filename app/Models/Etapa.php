<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\RegistraAuditoriaLog;

class Etapa extends Model
{
    use RegistraAuditoriaLog;
    protected $fillable = [
        'numero',
        'nome',
        'descricao'
    ];
}
