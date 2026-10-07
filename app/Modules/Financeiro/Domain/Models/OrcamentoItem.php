<?php

namespace App\Modules\Financeiro\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\RegistraAuditoria;

class OrcamentoItem extends Model
{
    use RegistraAuditoria;

    protected $table = 'orcamento_itens';
    protected $guarded = ['id'];

    /**
     * Calcula o total anual dinamicamente
     */
    public function getValorTotalAttribute()
    {
        return $this->valor_jan + $this->valor_fev + $this->valor_mar + 
               $this->valor_abr + $this->valor_mai + $this->valor_jun + 
               $this->valor_jul + $this->valor_ago + $this->valor_set + 
               $this->valor_out + $this->valor_nov + $this->valor_dez;
    }

    public function orcamento()
    {
        return $this->belongsTo(Orcamento::class);
    }

    /**
     * Novo relacionamento: Cada item (linha) é uma Natureza Financeira
     */
    public function natureza()
    {
        return $this->belongsTo(Natureza::class, 'natureza_codigo', 'codigo');
    }

    public function avaliacoes()
    {
        return $this->hasMany(OrcamentoAvaliacao::class)->orderBy('created_at', 'desc');
    }
}