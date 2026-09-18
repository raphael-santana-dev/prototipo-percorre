<?php

namespace App\Modules\Financeiro\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\RegistraAuditoria;

class Orcamento extends Model
{
    use RegistraAuditoria;
    protected $table = 'orcamentos';
    
    protected $guarded = ['id'];

    /**
     * Retorna o valor total do orçamento (Soma de todos os meses)
     */
    public function getValorTotalAttribute()
    {
        return $this->valor_jan + $this->valor_fev + $this->valor_mar + 
               $this->valor_abr + $this->valor_mai + $this->valor_jun + 
               $this->valor_jul + $this->valor_ago + $this->valor_set + 
               $this->valor_out + $this->valor_nov + $this->valor_dez;
    }
}