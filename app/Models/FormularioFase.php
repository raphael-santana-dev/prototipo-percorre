<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Traits\RegistraAuditoria;

class FormularioFase extends Model
{
    use RegistraAuditoria;
    protected $table = 'formulario_fases';

    protected $fillable = [
        'ciclo_id',
        'formulario_id',
        'nome',
        'paginas',
        'data_liberacao',
        'data_encerramento',
        'email_template_id',
        'notificacao_enviada_em',
    ];

    protected $casts = [
        'paginas'                => 'array',
        'data_liberacao'         => 'datetime',
        'data_encerramento'      => 'datetime',
        'notificacao_enviada_em' => 'datetime',
    ];

    public function ciclo()
    {
        return $this->belongsTo(Ciclo::class, 'ciclo_id');
    }

    public function formulario()
    {
        return $this->belongsTo(Formulario::class, 'formulario_id');
    }

    public function emailTemplate()
    {
        return $this->belongsTo(\App\Modules\Comunicacao\Domain\Models\EmailTemplate::class, 'email_template_id');
    }

    /**
     * Retorna os códigos de gatilho compatíveis com esta fase para o AutomacaoService.
     */
    public function obterGatilhosDisparo(): array
    {
        $slugNome = Str::slug($this->nome, '_');
        $gatilhos = [
            // 1. Gatilho específico pelo ID da Fase (mais preciso)
            "formulario.fase_liberada.{$this->id}",
        ];

        if ($this->ciclo_id) {
            // 2. Gatilho por slug da fase no ciclo e gatilho geral de fases do ciclo
            $gatilhos[] = "inscricao.fase.{$slugNome}";
            $gatilhos[] = "inscricao.fase_liberada";
        }

        if ($this->formulario_id) {
            // 3. Gatilho por slug da fase no formulário e gatilho geral de fases de formulários
            $gatilhos[] = "formulario.fase.{$slugNome}";
            $gatilhos[] = "formulario.fase_liberada";
        }

        return array_values(array_unique($gatilhos));
    }
}