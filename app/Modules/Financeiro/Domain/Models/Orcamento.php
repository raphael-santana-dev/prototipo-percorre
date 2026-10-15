<?php

namespace App\Modules\Financeiro\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\RegistraAuditoria;

class Orcamento extends Model
{
    use RegistraAuditoria;
    protected $table = 'orcamentos';
    
    // Adicione 'dados_consolidados' dentro do seu array $fillable existente:
    protected $fillable = [
        'chave_composta',
        'filial',
        'ano',
        'ccusto',
        'moeda',
        'cmoeda',
        'xcat',
        'descricao_despesa',
        'status',
        'dados_consolidados',
        'reabertura_solicitada',
        'reabertura_motivo',
        'prazo_edicao',
    ];

    // Adicione o cast para array:
    protected $casts = [
        'dados_consolidados' => 'array',
        'reabertura_solicitada' => 'boolean',
        'prazo_edicao' => 'datetime',
    ];

    // Gatilho automático ao aprovar o orçamento:
    protected static function booted(): void
    {
        static::saved(function (Orcamento $orcamento) {
            if ($orcamento->wasChanged('status')) {
                if (in_array($orcamento->status, ['Aprovado', 'Aprovado com ressalvas'])) {
                    app(\App\Modules\Financeiro\Services\ProtheusOrcamentoService::class)
                        ->enviarParaProtheusSimulado($orcamento);
                } elseif (in_array($orcamento->status, ['Criado', 'Em elaboração', 'Reprovado'])) {
                    // Se o orçamento for reaberto ou reprovado, limpa a consolidação anterior
                    $orcamento->updateQuietly(['dados_consolidados' => null]);
                }
            }
        });
    }

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