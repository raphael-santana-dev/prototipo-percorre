<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\RegistraAuditoria;

class OfertaVaga extends Model
{
    use RegistraAuditoria;
    protected $table = 'ofertas_vagas';
    
    protected $fillable = [
        'ciclo_id', 'curso_id', 'unidade_id', 'turno_id', 'vagas', 'idade_min', 'idade_max'
    ];

    public function ciclo() { return $this->belongsTo(Ciclo::class); }
    public function curso() { return $this->belongsTo(Curso::class); }
    public function unidade() { return $this->belongsTo(\App\Modules\Unidade\Domain\Models\Unidade::class, 'unidade_id'); }
    public function turno() { return $this->belongsTo(\App\Modules\Turno\Domain\Models\Turno::class, 'turno_id'); }

   
    public function vagasOcupadas()
    {
        return Inscricao::where('ciclo_id', $this->ciclo_id)
            ->where('curso_id', $this->curso_id)
            ->where('unidade_id', $this->unidade_id)
            ->where('turno_id', $this->turno_id)
            ->whereHas('statusInscricao', function($q) {
                $q->whereIn('nome', ['Aprovado', 'aprovado', 'Selecionado', 'selecionado']);
            })
            ->count();
    }

    
    public function temVagaDisponivel()
    {
        return $this->vagas > $this->vagasOcupadas();
    }
}