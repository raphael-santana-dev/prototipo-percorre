<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Inscricao extends Model
{
    use SoftDeletes;
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
}
