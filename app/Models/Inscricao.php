<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\FiltraPorVinculo;
use App\Traits\RegistraAuditoria;

class Inscricao extends Model
{
    use SoftDeletes;
    use FiltraPorVinculo;
    use RegistraAuditoria;

    public $moduloPermissao = 'inscricoes';
    
    protected $table = 'inscricoes';
    protected $fillable = [
        'ciclo_id',
        'student_id',
        'etapa_atual',
        'nome',
        'email',
        'celular',
        'possui_nome_social',
        'nome_social',
        'data_nascimento',
        'cpf',
        'cep',
        'logradouro',
        'numero',
        'complemento',
        'bairro',
        'cidade',
        'estado',
        'possui_deficiencia',
        'natureza_deficiencia',
        'receber_informacoes',
        'autorizacao_uso_infos',
        'pontuacao_total',
        'pontuacao_detalhes',
        'posicao_ranking',
        'dados_dinamicos',
        'metadados', 
        'curso_id',
        'turno_id',
        'unidade_id',
        'status_inscricao_id',
        'posicao_ranking_geral',
        'posicao_ranking_unidade',
        'posicao_ranking_curso',
        'slug',
        'criado_por',
        'origem',
        'deseja_informar',
        'unidade_interesse_id',
        'curso_interesse_id',
        'turno_interesse_id',
        'data_inscricao',
        'legado',
    ];

    protected $casts = [
        'dados_dinamicos' => 'array',
        'metadados' => 'array', 
        'pontuacao_detalhes' => 'array',
        'data_nascimento' => 'date',
        'status' => 'boolean',
        'data_inscricao' => 'datetime',
        'legado' => 'boolean'
    ];

    protected static function booted(): void
    {
        static::saved(function (Inscricao $inscricao) {
            if ($inscricao->legado || !$inscricao->ciclo_id || !$inscricao->status_inscricao_id) {
                return;
            }

            if (!$inscricao->wasRecentlyCreated && !$inscricao->wasChanged('status_inscricao_id')) {
                return;
            }

            $ciclo = $inscricao->relationLoaded('ciclo')
                ? $inscricao->ciclo
                : \App\Models\Ciclo::find($inscricao->ciclo_id);

            $statusAlvoAcesso = $ciclo?->status_acesso_estudante_id;

            if (!empty($statusAlvoAcesso) && (int) $inscricao->status_inscricao_id === (int) $statusAlvoAcesso) {
                \App\Modules\Student\Services\StudentAccessService::garantirAcesso(
                    $inscricao,
                    marcarMatriculado: false,
                    dispararNotificacao: true
                );
            }
        });
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
    
    public function ciclo()
    {
        return $this->belongsTo(Ciclo::class, 'ciclo_id');
    }

    public function unidade()
    {
        return $this->belongsTo(\App\Modules\Unidade\Domain\Models\Unidade::class, 'unidade_id');
    }

    public function curso()
    {
        return $this->belongsTo(Curso::class, 'curso_id');
    }

    public function turno()
    {
        return $this->belongsTo(\App\Modules\Turno\Domain\Models\Turno::class, 'turno_id');
    }
    
    public function statusInscricao()
    {
        return $this->belongsTo(StatusInscricao::class, 'status_inscricao_id');
    }

    public function criador()
    {
        return $this->belongsTo(User::class, 'criado_por');
    }

    public function contatos()
    {
        return $this->hasMany(\App\Models\InscricaoContato::class, 'inscricao_id')
                    ->orderBy('data_contato', 'desc')
                    ->orderBy('id', 'desc');
    }

    public function comunicados()
    {
        return $this->hasMany(\App\Modules\Comunicacao\Domain\Models\Comunicado::class, 'inscricao_id');
    }
}