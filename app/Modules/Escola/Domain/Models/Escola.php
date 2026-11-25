<?php

namespace App\Modules\Escola\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\RegistraAuditoria;

class Escola extends Model
{
    use SoftDeletes, RegistraAuditoria;

    protected $table = 'escolas';

    protected $fillable = [
        'nome',
        'tipo',
        'regiao',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];
}