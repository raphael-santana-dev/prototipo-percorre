<?php

namespace App\Modules\Financeiro\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class OrcamentoAvaliacao extends Model
{
    protected $table = 'orcamento_avaliacoes';
    protected $guarded = ['id'];

    public function orcamento()
    {
        return $this->belongsTo(Orcamento::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}