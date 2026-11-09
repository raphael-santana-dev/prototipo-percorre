<?php

namespace App\Traits;

use App\Models\AuditoriaLog;
use Illuminate\Support\Facades\Auth;

trait ValidaAcesso
{
    /**
     * Uso no mount(): Bloqueia a abertura da página e renderiza a view errors/403.blade.php
     * diferenciando Feature Toggle desativada de Permissão ACL ausente.
     */
    protected function autorizarTela(string $chave, ?string $nomeModuloAmigavel = null): void
    {
        $rotulo = $nomeModuloAmigavel ? "O módulo de {$nomeModuloAmigavel}" : 'Esta funcionalidade';

        // 1. Verifica Feature Toggle
        if (!feature($chave)) {
            $this->registrarLogAcessoNegado($chave, 'Feature Desativada', "Tentativa de abrir a tela: {$nomeModuloAmigavel}");
            abort(403, "{$rotulo} está temporariamente desativado no sistema.");
        }

        // 2. Verifica Permissão ACL Spatie
        $user = Auth::user();
        if (!$user || (!$user->hasRole('dev') && !$user->can($chave))) {
            $this->registrarLogAcessoNegado($chave, 'Falta de Permissão ACL', "Tentativa de abrir a tela: {$nomeModuloAmigavel}");
            abort(403, 'Acesso restrito ao seu perfil de usuário.');
        }
    }

    /**
     * Uso em métodos de clique do Livewire (salvar, excluir, abrirModal, etc.):
     * Exibe um alerta Toast amigável na tela sem quebrar a requisição AJAX do Livewire.
     */
    protected function autorizarAcao(string $chave, ?string $acaoAmigavel = null): bool
    {
        // 1. Verifica Feature Toggle
        if (!feature($chave)) {
            $msg = $acaoAmigavel
                ? "A funcionalidade para {$acaoAmigavel} está temporariamente desativada no sistema."
                : 'Esta funcionalidade está temporariamente desativada no sistema.';

            $this->registrarLogAcessoNegado($chave, 'Feature Desativada', "Tentativa de executar ação: {$acaoAmigavel}");
            $this->dispatch('erro', msg: $msg);
            return false;
        }

        // 2. Verifica Permissão ACL Spatie
        $user = Auth::user();
        if (!$user || (!$user->hasRole('dev') && !$user->can($chave))) {
            $msg = $acaoAmigavel
                ? "O seu perfil não possui permissão para {$acaoAmigavel}."
                : 'Você não possui permissão para realizar esta operação.';

            $this->registrarLogAcessoNegado($chave, 'Falta de Permissão ACL', "Tentativa de executar ação: {$acaoAmigavel}");
            $this->dispatch('erro', msg: $msg);
            return false;
        }

        return true;
    }

    /**
     * Registra o bloqueio de segurança no log de auditoria global do sistema.
     */
    private function registrarLogAcessoNegado(string $permissao, string $motivo, string $contexto): void
    {
        $user = Auth::user();

        AuditoriaLog::create([
            'tabela_alterada' => 'sistema_seguranca',
            'registro_id'     => null,
            'acao'            => 'acesso_negado',
            'informacao_anterior' => null,
            'nova_informacao' => [
                'permissao_exigida' => $permissao,
                'motivo_bloqueio'   => $motivo,
                'contexto'          => $contexto,
                'url_acessada'      => request()->fullUrl(),
            ],
            'usuario_id'      => $user?->id,
            'usuario_nome'    => $user?->name ?? 'Usuário Não Autenticado',
            'usuario_role'    => ($user && method_exists($user, 'getRoleNames')) ? $user->getRoleNames()->first() : 'N/A',
            'usuario_login'   => $user?->email,
            'ip'              => request()->ip(),
            'navegador'       => request()->userAgent(),
        ]);
    }
}