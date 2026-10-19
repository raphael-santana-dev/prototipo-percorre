<?php

namespace App\Modules\Comunicacao\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\RegistraAuditoria;
use App\Models\Inscricao;

class Comunicado extends Model
{
    use RegistraAuditoria;

    protected $table = 'comunicados';
    protected $guarded = [];

    protected $casts = [
        'destinatarios' => 'array',
        'cc' => 'array',
        'bcc' => 'array',
        'anexos' => 'array',
        'data_agendamento' => 'datetime',
        'aberto_em' => 'datetime',
        'ultima_abertura_em' => 'datetime',
        'clicado_em' => 'datetime',
        'ultimo_clique_em' => 'datetime',
    ];

    public $fillable = [
        'inscricao_id',
        'template_id',
        'destinatarios',
        'cc',
        'bcc',
        'anexos',
        'data_agendamento',
        'status',
        'aberturas_count',
        'aberto_em',
        'ultima_abertura_em',
        'cliques_count',
        'clicado_em',
        'ultimo_clique_em',
    ];

    public function template()
    {
        return $this->belongsTo(EmailTemplate::class, 'template_id');
    }

    public function inscricao()
    {
        return $this->belongsTo(Inscricao::class, 'inscricao_id');
    }

    public function logs()
    {
        return $this->hasMany(ComunicacaoLog::class, 'comunicado_id');
    }

    public function eventos()
    {
        return $this->hasMany(ComunicacaoLogEvento::class, 'comunicado_id')->latest();
    }

    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'pendente' => 'bg-yellow-100 text-yellow-800',
            'enviando' => 'bg-blue-100 text-blue-800',
            'concluido' => 'bg-green-100 text-green-800',
            'erro' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}