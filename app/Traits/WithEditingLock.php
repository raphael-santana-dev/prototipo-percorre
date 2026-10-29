<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;

trait WithEditingLock
{
    public bool $isLockedByOther = false;
    public string $lockOwnerName = '';
    public ?int $lockOwnerId = null;

    public bool $hasTransferRequest = false;
    public string $requesterName = '';
    public ?int $requesterId = null;

    public bool $transferRejected = false;
    public bool $transferAccepted = false;

    // Estas propriedades devem ser definidas no mount() do componente que usa a Trait
    public string $resourceLockType = 'recurso';
    public int $resourceLockId = 0;

    protected function getLockKey()
    {
        return "edit_lock_{$this->resourceLockType}_{$this->resourceLockId}";
    }

    protected function getTransferKey()
    {
        return "edit_transfer_{$this->resourceLockType}_{$this->resourceLockId}";
    }

    public function checkAndAcquireLock()
    {
        $lockKey = $this->getLockKey();
        $currentLock = Cache::get($lockKey);

        if ($currentLock && $currentLock['user_id'] !== auth()->id() && $currentLock['expires_at'] > now()->timestamp) {
            // Outro utilizador tem o controlo
            $this->isLockedByOther = true;
            $this->lockOwnerName = $currentLock['user_name'];
            $this->lockOwnerId = $currentLock['user_id'];
            
            // Verifica se eu solicitei o controlo e houve resposta
            $transferData = Cache::get($this->getTransferKey());
            if ($transferData && $transferData['requester_id'] === auth()->id()) {
                if (($transferData['status'] ?? '') === 'rejected') {
                    $this->transferRejected = true;
                } elseif (($transferData['status'] ?? '') === 'accepted') {
                    $this->takeOverLock();
                }
            }
        } else {
            // O recurso está livre ou o Lock já era meu, renovo o tempo
            $this->isLockedByOther = false;
            Cache::put($lockKey, [
                'user_id' => auth()->id(),
                'user_name' => auth()->user()->name,
                'expires_at' => now()->addMinutes(2)->timestamp
            ], now()->addMinutes(2));
        }
    }

    public function pingLock()
    {
        if (!$this->isLockedByOther) {
            $this->checkAndAcquireLock();
            
            // Sou o dono. Verifico se alguém está a pedir o controlo
            $transferData = Cache::get($this->getTransferKey());
            if ($transferData && ($transferData['status'] ?? 'pending') === 'pending') {
                $this->hasTransferRequest = true;
                $this->requesterName = $transferData['requester_name'];
                $this->requesterId = $transferData['requester_id'];
            } else {
                $this->hasTransferRequest = false;
            }
        } else {
            // Estou bloqueado. Continuo a verificar se o dono saiu ou aceitou o meu pedido
            $this->checkAndAcquireLock();
        }
    }

    public function requestControl()
    {
        Cache::put($this->getTransferKey(), [
            'requester_id' => auth()->id(),
            'requester_name' => auth()->user()->name,
            'status' => 'pending'
        ], now()->addMinutes(5));
        
        $this->transferRejected = false;
        
        if (method_exists($this, 'dispatch')) {
            $this->dispatch('sucesso', msg: 'Solicitação enviada! Aguarde a resposta do utilizador.');
        }
    }

    public function cancelTransferRequest()
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
            
            // Abandono o meu Lock
            Cache::forget($this->getLockKey());
            $this->isLockedByOther = true;
            $this->hasTransferRequest = false;
            
            return redirect()->route('dashboard'); // Expulsa o utilizador atual em segurança
        }
    }

    public function rejectTransfer()
    {
        $transferData = Cache::get($this->getTransferKey());
        if ($transferData) {
            $transferData['status'] = 'rejected';
            Cache::put($this->getTransferKey(), $transferData, now()->addMinutes(2));
            $this->hasTransferRequest = false;
        }
    }

    private function takeOverLock()
    {
        Cache::forget($this->getTransferKey());
        $this->isLockedByOther = false;
        $this->transferAccepted = true;
        $this->checkAndAcquireLock();
        
        if (method_exists($this, 'dispatch')) {
            $this->dispatch('sucesso', msg: 'Controlo transferido com sucesso! Já pode editar.');
        }
    }
}