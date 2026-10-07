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
     * Retorna o valor total do orçamento somando todos os itens (naturezas) vinculados a ele
     */
    public function getValorTotalAttribute()
    {
        if ($this->itens && $this->itens->count() > 0) {
            return $this->itens->sum(function ($item) {
                return $item->valor_total;
            });
        }
        
        return 0;
    }

    public function avaliacoes()
    {
        return $this->hasMany(OrcamentoAvaliacao::class)->orderBy('created_at', 'desc');
    }

    public function itens()
    {
        return $this->hasMany(OrcamentoItem::class)->orderBy('created_at', 'asc');
    }

    /**
     * Relacionamento: Este orçamento pertence a um Centro de Custo
     */
    public function centroCusto()
    {
        return $this->belongsTo(CentroCusto::class, 'ccusto', 'codigo');
    }
    
    public function logsGerais()
    {
        return $this->hasMany(OrcamentoAvaliacao::class, 'orcamento_id')->orderBy('created_at', 'desc');
    }
}