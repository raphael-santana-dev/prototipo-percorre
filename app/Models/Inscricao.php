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
        'curso_id',
        'turno_id',
        'unidade_id',
        'status_inscricao_id',
        'posicao_ranking_geral',
        'posicao_ranking_unidade',
        'posicao_ranking_curso',
    ];

    protected $casts = [
        'dados_dinamicos' => 'array',
        'pontuacao_detalhes' => 'array',
        'data_nascimento' => 'date',
        'status' => 'boolean'
    ];

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
}
