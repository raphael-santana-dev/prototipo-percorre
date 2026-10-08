<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\RegistraAuditoria;

class Ciclo extends Model
{
    use RegistraAuditoria;
    protected $fillable = [
        'nome', 'ano', 'semestre', 'data_inicio', 'data_fim', 'status', 'regras_pontuacao', 'slug', 'bloqueado'
    ];

    protected $casts = [
        'data_inicio' => 'datetime',
        'data_fim' => 'datetime',
        'status' => 'boolean',
        'regras_pontuacao' => 'array',
        'bloqueado' => 'boolean',
    ];

    public function inscricoes()
    {
        return $this->hasMany(Inscricao::class);
    }

    public function campos()
    {
        return $this->hasMany(CampoFormulario::class)->orderBy('ordem');
    }

    public function cursos()
    {
        return $this->belongsToMany(Curso::class, 'ciclo_curso');
    }

    public function unidades()
    {
        return $this->belongsToMany(\App\Modules\Unidade\Domain\Models\Unidade::class, 'ciclo_unidade', 'ciclo_id', 'unidade_id');
    }

    public function turnos()
    {
        return $this->belongsToMany(\App\Modules\Turno\Domain\Models\Turno::class, 'ciclo_turno', 'ciclo_id', 'turno_id');
    }

    public function statusPipeline()
    {
        return $this->belongsToMany(StatusInscricao::class, 'ciclo_status_inscricao')
            ->withPivot('ordem')
            ->orderBy('pivot_ordem', 'asc');
    }

    /**
     * Retorna o ID do primeiro Status (Etapa 1 do funil) configurado para este ciclo.
     * Caso o administrador não tenha configurado o funil, retorna um fallback seguro.
     */
    public function getStatusInicialId(): int
    {
        // Tenta buscar a primeira etapa configurada no funil (menor ordem)
        $primeiroStatus = $this->statusPipeline()->orderBy('pivot_ordem', 'asc')->first();

        if ($primeiroStatus) {
            return $primeiroStatus->id;
        }

        // Fallback: Se o ciclo estiver sem funil configurado, tenta pegar o status padrão de sucesso
        $statusFallback = \App\Models\StatusInscricao::where('nome', 'Inscrição Finalizada')->first();

        // Se nem o fallback existir, devolve o ID 1 por extrema segurança
        return $statusFallback ? $statusFallback->id : 1;
    }
}
