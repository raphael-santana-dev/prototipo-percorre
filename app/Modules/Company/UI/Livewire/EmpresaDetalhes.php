<?php

namespace App\Modules\Company\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Modules\Company\Domain\Models\Empresa;

#[Layout('components.layouts.app')]
#[Title('Detalhes da Empresa - Integração')]
class EmpresaDetalhes extends Component
{
    public Empresa $empresa;
    public string $abaAtual = 'aprendizes'; 
    public array $breadcrumbs = [];

    public function mount($id)
    {
        $this->empresa = Empresa::with([
            'companyUsers',
            'aprendizes.unidade', 
            'aprendizes.gestor'
        ])->findOrFail($id);

        $this->breadcrumbs = [
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Secretaria', 'url' => '#'],
            ['label' => 'Empresas Parceiras', 'url' => route('empresas.index')],
            ['label' => $this->empresa->nome_fantasia ?? $this->empresa->razao_social, 'url' => '#'],
        ];
    }

    public function setAba($aba)
    {
        $this->abaAtual = $aba;
    }

    public function render()
    {
        return view('livewire.company.empresa-detalhes');
    }
}