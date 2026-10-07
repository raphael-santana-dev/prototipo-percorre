<?php

namespace App\Modules\Financeiro\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class OrcamentoAvaliacao extends Model
{
    protected $table = 'orcamento_avaliacoes';
    protected $guarded = ['id'];

    // Relacionamento com o Item (Log Específico)
    public function orcamentoItem()
    {
        return $this->belongsTo(OrcamentoItem::class, 'orcamento_item_id');
    }

    // Relacionamento com o Cabeçalho (Log Geral)
    public function orcamento()
    {
        return $this->belongsTo(Orcamento::class, 'orcamento_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}