<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatusInscricao extends Model
{
    protected $table = 'status_inscricoes'; // Define a tabela explicitamente
    
    protected $fillable = [
        'nome', 'descricao',
    ];
}