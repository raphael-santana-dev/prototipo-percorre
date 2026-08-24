<?php

namespace App\Modules\Company\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Auth;

#[Layout('components.layouts.company')]
#[Title('Portal da Empresa - Dashboard')]
class Dashboard extends Component
{
    public function render()
    {
        $usuario = Auth::guard('company')->user();

        // Aqui, no futuro, buscaremos os dados reais do Protheus ou das avaliações geradas
        $metricas = [
            'total_aprendizes' => 0,
            'avaliacoes_pendentes' => 0,
            'avaliacoes_concluidas' => 0,
        ];

        return view('livewire.company.dashboard', [
            'usuario' => $usuario,
            'metricas' => $metricas
        ]);
    }
}