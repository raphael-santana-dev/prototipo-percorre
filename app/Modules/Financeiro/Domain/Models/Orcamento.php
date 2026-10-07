<?php

namespace App\Modules\Financeiro\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\RegistraAuditoria;

class Orcamento extends Model
{
    use RegistraAuditoria;
    protected $table = 'orcamentos';
    
    protected $guarded = ['id'];

    public function avaliacoes()
    {
        return $this->hasMany(OrcamentoAvaliacao::class)->orderBy('created_at', 'desc');
    }

    public function itens()
    {
        return $this->hasMany(OrcamentoItem::class)->orderBy('created_at', 'asc');
    }
    
    public function getValorTotalAttribute()
    {
        // Se houver itens, soma o valor total de cada um deles
        if ($this->itens && $this->itens->count() > 0) {
            return $this->itens->sum(function ($item) {
                return $item->valor_total;
            });
        }
        
        // Fallback: se não tiver itens, retorna a soma das colunas base (legado)
        return $this->valor_jan + $this->valor_fev + $this->valor_mar + 
               $this->valor_abr + $this->valor_mai + $this->valor_jun + 
               $this->valor_jul + $this->valor_ago + $this->valor_set + 
               $this->valor_out + $this->valor_nov + $this->valor_dez;
    }

    
}