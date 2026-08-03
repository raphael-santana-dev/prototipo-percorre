<?php

namespace App\Traits;

use App\Models\AuditoriaLog;
use Illuminate\Support\Facades\Auth;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait RegistraAuditoria
{
    public static function bootRegistraAuditoria()
    {
        static::created(function ($model) {
            self::salvarLogAuditoria($model, 'criacao');
        });

        static::updated(function ($model) {
            self::salvarLogAuditoria($model, 'atualizacao');
        });

        static::deleted(function ($model) {
            self::salvarLogAuditoria($model, 'exclusao');
        });
    }

    protected static function salvarLogAuditoria($model, $acao)
    {
        $antigo = null;
        $novo = null;

        if ($acao === 'criacao') {
            $novo = $model->getAttributes();
        } elseif ($acao === 'atualizacao') {
            $novo = $model->getChanges();
            // Pega o estado original apenas das colunas que sofreram alteração
            $antigo = array_intersect_key($model->getOriginal(), $novo);
            
            // Prevenção: Se clicou em salvar sem alterar nada, ignora o log
            if (empty($novo)) return; 
        } elseif ($acao === 'exclusao') {
            $antigo = $model->getOriginal();
        }

        $usuario = Auth::user();

        AuditoriaLog::create([
            'tabela_alterada' => $model->getTable(),
            'registro_id' => $model->getKey(),
            'acao' => $acao,
            'informacao_anterior' => $antigo,
            'nova_informacao' => $novo,
            'usuario_id' => $usuario?->id,
            'usuario_nome' => $usuario?->name ?? 'Ação de Sistema',
            'usuario_role' => ($usuario && method_exists($usuario, 'getRoleNames')) ? $usuario->getRoleNames()->first() : 'N/A',
            'usuario_login' => $usuario?->email,
            'ip' => request()->ip(),
            'navegador' => request()->userAgent(),
        ]);
    }
}