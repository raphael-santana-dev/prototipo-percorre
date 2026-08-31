<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\RegistraAuditoria;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Etapa extends Model
{
    use RegistraAuditoria, HasFactory;
    protected $fillable = ['ciclo_id', 'formulario_id', 'numero', 'nome', 'descricao'];
}
