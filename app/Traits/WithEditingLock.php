<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;

trait WithEditingLock
{
    public bool $isLockedByOther = false;
    public string $lockOwnerName = '';
    public ?int $lockOwnerId = null;
    public string $lockContextLabel = '';

    public bool $hasTransferRequest = false;
    public string $requesterName = '';
    public ?int $requesterId = null;

    public bool $transferRejected = false;
    public bool $transferAccepted = false;

    public string $resourceLockType = 'recurso';
    public int $resourceLockId = 0;
    public string $lockReturnRoute = 'dashboard';

    protected function getLockKey(?string $type = null, ?int $id = null): string
    {
        $t = $type ?? $this->resourceLockType;
        $i = $id ?? $this->resourceLockId;
        return "edit_lock_{$t}_{$i}";
    }

    protected function getTransferKey(?string $type = null, ?int $id = null): string
    {
        $t = $type ?? $this->resourceLockType;
        $i = $id ?? $this->resourceLockId;
        return "edit_transfer_{$t}_{$i}";
    }

    /**
     * Atalho para iniciar o lock tanto no mount() de páginas quanto ao abrir Modais de edição.
     */
    public function iniciarLockEdicao(string $tipo, int $id, string $labelContexto = 'Cadastro', string $rotaRetorno = 'dashboard'): void
    {
        $this->resourceLockType = $tipo;
        $this->resourceLockId = $id;
        $this->lockContextLabel = $labelContexto;
        $this->lockReturnRoute = $rotaRetorno;
        $this->transferRejected = false;
        $this->transferAccepted = false;

        $this->checkAndAcquireLock();
    }

    /**
     * Libera imediatamente o lock (usado ao salvar ou fechar modais de edição).
     */
    public function liberarLockEdicao(): void
    {
        if ($this->resourceLockId > 0 && !$this->isLockedByOther) {
            $lockKey = $this->getLockKey();
            $currentLock = Cache::get($lockKey);

            if ($currentLock && ($currentLock['user_id'] ?? null) === auth()->id()) {
                Cache::forget($lockKey);
                Cache::forget($this->getTransferKey());
            }
        }

        $this->isLockedByOther = false;
        $this->hasTransferRequest = false;
        $this->resourceLockId = 0;
    }

    public function checkAndAcquireLock(): void
    {
        if ($this->resourceLockId <= 0) {
            return;
        }

        $lockKey = $this->getLockKey();
        $currentLock = Cache::get($lockKey);

        if ($currentLock && ($currentLock['user_id'] ?? null) !== auth()->id() && ($currentLock['expires_at'] ?? 0) > now()->timestamp) {
            $this->isLockedByOther = true;
            $this->lockOwnerName = (string) ($currentLock['user_name'] ?? 'Outro utilizador');
            $this->lockOwnerId = (int) ($currentLock['user_id'] ?? 0);
            $this->lockContextLabel = (string) ($currentLock['context_label'] ?? $this->lockContextLabel ?: 'Em Edição');

            $transferData = Cache::get($this->getTransferKey());
            if ($transferData && ($transferData['requester_id'] ?? null) === auth()->id()) {
                if (($transferData['status'] ?? '') === 'rejected') {
                    $this->transferRejected = true;
                } elseif (($transferData['status'] ?? '') === 'accepted') {
                    $this->takeOverLock();
                }
            }
        } else {
            $this->isLockedByOther = false;
            $labelPadrao = $this->lockContextLabel ?: $this->resolverLabelPadrao($this->resourceLockType);

            Cache::put($lockKey, [
                'user_id'       => auth()->id(),
                'user_name'     => auth()->user()->name ?? 'Utilizador',
                'resource_type' => $this->resourceLockType,
                'resource_id'   => $this->resourceLockId,
                'context_label' => $labelPadrao,
                'updated_at'    => now()->format('H:i:s'),
                'expires_at'    => now()->addSeconds(45)->timestamp,
            ], now()->addSeconds(45));
        }
    }

    protected function resolverLabelPadrao(string $type): string
    {
        return match ($type) {
            'ciclo_dados'          => 'Dados do Ciclo',
            'ciclo'                => 'Formulário',
            'ciclo_regras'         => 'Regras de Pontuação',
            'formulario'           => 'Formulário',
            'template_comunicacao' => 'Template',
            'usuario'              => 'Usuário',
            'role'                 => 'Perfil de Acesso',
            'permissao'            => 'Permissão',
            'curso'                => 'Curso',
            'unidade'              => 'Unidade',
            'inscricao'            => 'Inscrição',
            default                => 'Em Edição',
        };
    }

    public function pingLock(): void
    {
        if ($this->resourceLockId <= 0) {
            return;
        }

        if (!$this->isLockedByOther) {
            $this->checkAndAcquireLock();

            $transferData = Cache::get($this->getTransferKey());
            if ($transferData && ($transferData['status'] ?? 'pending') === 'pending') {
                $this->hasTransferRequest = true;
                $this->requesterName = (string) ($transferData['requester_name'] ?? 'Utilizador');
                $this->requesterId = (int) ($transferData['requester_id'] ?? 0);
            } else {
                $this->hasTransferRequest = false;
            }
        } else {
            $this->checkAndAcquireLock();
        }
    }

    public function requestControl(): void
    {
        if ($this->resourceLockId <= 0) return;

        Cache::put($this->getTransferKey(), [
            'requester_id'   => auth()->id(),
            'requester_name' => auth()->user()->name ?? 'Utilizador',
            'status'         => 'pending',
        ], now()->addMinutes(5));

        $this->transferRejected = false;

        if (method_exists($this, 'dispatch')) {
            $this->dispatch('sucesso', msg: 'Solicitação enviada! Aguarde a resposta do utilizador.');
        }
    }

    public function cancelTransferRequest(): void
    {
        Cache::forget($this->getTransferKey());
        $this->transferRejected = false;
    }

    public function acceptTransfer()
    {
        $transferData = Cache::get($this->getTransferKey());
        if ($transferData) {
            $transferData['status'] = 'accepted';
            Cache::put($this->getTransferKey(), $transferData, now()->addMinutes(2));

            Cache::forget($this->getLockKey());
            $this->isLockedByOther = true;
            $this->hasTransferRequest = false;

            $rota = \Illuminate\Support\Facades\Route::has($this->lockReturnRoute)
                ? route($this->lockReturnRoute)
                : route('dashboard');

            return redirect()->to($rota);
        }
    }

    public function rejectTransfer(): void
    {
        $transferData = Cache::get($this->getTransferKey());
        if ($transferData) {
            $transferData['status'] = 'rejected';
            Cache::put($this->getTransferKey(), $transferData, now()->addMinutes(2));
            $this->hasTransferRequest = false;
        }
    }

    private function takeOverLock(): void
    {
        Cache::forget($this->getTransferKey());
        $this->isLockedByOther = false;
        $this->transferAccepted = true;
        $this->checkAndAcquireLock();

        if (method_exists($this, 'dispatch')) {
            $this->dispatch('sucesso', msg: 'Controlo transferido com sucesso! Já pode editar.');
        }
    }

    /**
     * Consulta todos os locks ativos de um registro (incluindo submódulos, ex: Ciclo + Formulário + Regras)
     * para exibir os badges em tempo real nas tabelas de listagem.
     *
     * @param int $id ID do registro na linha da tabela
     * @param array $tipos Mapa de ['tipo_lock' => 'Rótulo do Badge'] ou lista ['usuario']
     */
    public static function obterLocksAtivos(int $id, array $tipos): array
    {
        $ativos = [];
        $agora = now()->timestamp;

        foreach ($tipos as $chave => $valor) {
            $tipoLock = is_int($chave) ? $valor : $chave;
            $rotuloCustom = is_int($chave) ? null : $valor;

            $dados = Cache::get("edit_lock_{$tipoLock}_{$id}");
            if ($dados && ($dados['expires_at'] ?? 0) > $agora) {
                $ativos[] = [
                    'type'          => $tipoLock,
                    'user_id'       => $dados['user_id'] ?? null,
                    'user_name'     => $dados['user_name'] ?? 'Utilizador',
                    'context_label' => $rotuloCustom ?: ($dados['context_label'] ?? 'Em Edição'),
                    'is_me'         => ($dados['user_id'] ?? null) === auth()->id(),
                ];
            }
        }

        return $ativos;
    }
}