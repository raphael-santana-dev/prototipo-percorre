<?php

namespace App\Traits;

use App\Models\AuditoriaLog;
use Illuminate\Support\Facades\Auth;


trait RegistraAuditoriaLog
{
    
    // O método boot[NomeDaTrait] é executado automaticamente pelo Laravel em todos os Models que usarem esta Trait
    public static function bootRegistraAuditoriaLog()
    {
        static::created(function ($model) {
            self::salvarLog($model, 'criacao');
        });

        static::updated(function ($model) {
            self::salvarLog($model, 'atualizacao');
        });

        static::deleted(function ($model) {
            self::salvarLog($model, 'exclusao');
        });
    }

    protected static function salvarLog($model, $acao)
    {
        $usuario = Auth::user();
        
        $informacaoAnterior = null;
        $novaInformacao = null;

        if ($acao === 'atualizacao') {
            // Pega apenas os campos que foram realmente alterados (O que era antes)
            $informacaoAnterior = array_intersect_key($model->getOriginal(), $model->getChanges());
            // O que ficou agora
            $novaInformacao = $model->getChanges();
        } elseif ($acao === 'criacao') {
            $novaInformacao = $model->getAttributes();
        } elseif ($acao === 'exclusao') {
            $informacaoAnterior = $model->getOriginal();
        }

        // Se for atualização mas nada mudou (ex: clicou em salvar sem alterar), ignoramos
        if ($acao === 'atualizacao' && empty($novaInformacao)) {
            return;
        }

        AuditoriaLog::create([
            'tabela_alterada' => $model->getTable(),
            'registro_id' => $model->id,
            'acao' => $acao,
            'informacao_anterior' => $informacaoAnterior ? json_encode($informacaoAnterior) : null,
            'nova_informacao' => $novaInformacao ? json_encode($novaInformacao) : null,
            'usuario_id' => $usuario ? $usuario->id : null,
            'usuario_nome' => $usuario ? $usuario->name : 'Ação de Sistema / API',
            'usuario_role' => ($usuario && method_exists($usuario, 'getRoleNames')) ? $usuario->getRoleNames()->first() : 'N/A',
            'usuario_login' => $usuario ? $usuario->email : null, // Adapte para 'cpf' se for o caso
            'ip' => request()->ip(),
            'navegador' => request()->userAgent(),
        ]);
    }
}