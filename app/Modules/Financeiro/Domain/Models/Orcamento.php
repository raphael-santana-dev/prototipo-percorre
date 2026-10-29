<?php

namespace App\Modules\Financeiro\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\RegistraAuditoria;

class Orcamento extends Model
{
    use RegistraAuditoria;
    protected $table = 'orcamentos';
    
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

    protected $casts = [
        'dados_consolidados' => 'array',
        'reabertura_solicitada' => 'boolean',
        'prazo_edicao' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saved(function (Orcamento $orcamento) {
            if ($orcamento->wasChanged('status')) {
                if (in_array($orcamento->status, [
                    'Importado',
                    'Em revisão/atualização',
                    'Aprovação – Aguardando Revisão pelo Responsável',
                    'Criado',
                    'Em elaboração',
                    'Reprovado'
                ], true)) {
                    $orcamento->updateQuietly(['dados_consolidados' => null]);
                }
            }
        });
    }

    public function getValorTotalAttribute()
    {
        if ($this->itens && $this->itens->count() > 0) {
            return $this->itens->sum(function ($item) {
                return $item->valor_total;
            });
        }

        // Enquanto os itens detalhados ainda não foram abertos/importados, exibe o total vindo da listagem sem parâmetros
        if (is_array($this->dados_consolidados) && isset($this->dados_consolidados['total'])) {
            return (float) $this->dados_consolidados['total'];
        }
        
        return 0;
    }

    public function avaliacoes()
    {
        return $this->hasMany(OrcamentoAvaliacao::class)->orderBy('created_at', 'desc');
    }

    public function itens()
    {
        return $this->hasMany(OrcamentoItem::class)->orderBy('id', 'asc');
    }

    public function centroCusto()
    {
        return $this->belongsTo(CentroCusto::class, 'ccusto', 'codigo');
    }
    
    public function logsGerais()
    {
        return $this->hasMany(OrcamentoAvaliacao::class, 'orcamento_id')
            ->whereNull('orcamento_item_id')
            ->orderBy('created_at', 'desc');
    }
}