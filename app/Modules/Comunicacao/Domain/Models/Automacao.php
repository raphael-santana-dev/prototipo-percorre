<?php

namespace App\Modules\Comunicacao\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\RegistraAuditoria;

class Automacao extends Model
{
    
    use RegistraAuditoria;
    protected $table = 'automacoes';
    protected $guarded = [];
    protected $casts = [
        'status' => 'boolean',
    ];

    protected $fillable = [
        'nome',
        'evento_gatilho',
        'template_id',
        'tipo_acao',
        'status'
    ];

    public function template()
    {
        return $this->belongsTo(EmailTemplate::class, 'template_id');
    }
}