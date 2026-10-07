<?php

namespace App\Modules\Financeiro\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\RegistraAuditoria;

class OrcamentoHistorico extends Model
{
    use RegistraAuditoria;
    protected $table = 'orcamentos_historicos';
    
    protected $guarded = ['id'];
}