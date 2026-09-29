<?php

namespace App\Modules\Admin\UI\Livewire;

use Livewire\Component;
use App\Models\Solicitacao;
use Livewire\Attributes\On;

class NotificationBadge extends Component
{
    public $count = 0;

    public function mount()
    {
        $this->atualizarContador();
    }

    #[On('sucesso')]
    public function atualizarContador()
    {
        if (auth()->check() && auth()->user()->roles()->count() > 0) {
            
            $query = Solicitacao::where('status', 'pendente');

            if (auth()->user()->hasRole('professor') && !auth()->user()->hasRole('dev|admin')) {
                $query->where(function($q) {
                    $q->where('responsavel_id', auth()->id())
                      ->orWhereNull('responsavel_id');
                });
            }

            $this->count = $query->count();
        }
    }

    public function render()
    {
        if (!auth()->check() || auth()->user()->roles()->count() === 0) {
            return <<<'HTML'
            <div></div>
            HTML;
        }

        return view('livewire.admin.notification-badge');
    }
}