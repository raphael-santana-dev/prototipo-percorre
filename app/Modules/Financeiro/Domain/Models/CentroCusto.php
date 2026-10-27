<?php

namespace App\Modules\Financeiro\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\RegistraAuditoria;

class CentroCusto extends Model
{
    use RegistraAuditoria;

    protected $table = 'centros_custo';

    protected $fillable = [
        'codigo',
        'nome',
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

    public function users()
    {
        return $this->belongsToMany(\App\Models\User::class, 'centro_custo_user')
                    ->withPivot('expires_at')
                    ->withTimestamps();
    }
}