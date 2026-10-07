<?php

namespace App\Modules\Financeiro\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\RegistraAuditoria;

class OrcamentoItem extends Model
{
    use RegistraAuditoria; // Rastrear quem criou/alterou o item

    protected $table = 'orcamento_itens';
    protected $guarded = ['id'];

    /**
     * Calcula o total anual deste item dinamicamente
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
}