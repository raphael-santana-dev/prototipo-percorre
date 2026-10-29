<?php

namespace App\Modules\Financeiro\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\RegistraAuditoria;

class Natureza extends Model
{
    use RegistraAuditoria;

    protected $table = 'naturezas';

    protected $fillable = [
        'codigo',
        'descricao',
        'categoria',
        'tipo',
        'disponivel_orcamento',
    ];

    protected $casts = [
        'disponivel_orcamento' => 'boolean',
    ];

    /**
     * Retorna a descrição oficial do status conforme especificação do Projeto Lógico
     */
    public function getStatusDescricaoAttribute(): string
    {
        return $this->disponivel_orcamento ? 'Disponível para Orçamento' : 'Inativo';
    }
}