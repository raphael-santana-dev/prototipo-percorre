<?php

namespace App\Modules\Company\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use App\Modules\Company\Domain\Models\Empresa;
use App\Traits\ComPadraoListagem;

#[Layout('components.layouts.app')]
#[Title('Empresas Parceiras (Integração)')]
class EmpresaManager extends Component
{
    use WithPagination;
    use ComPadraoListagem; 

    public string $filtro_busca = '';
    public string $statusColumn = 'is_active';
    public $modelClass = Empresa::class;
    
    public array $breadcrumbs = [];

    public function mount()
    {
        $this->breadcrumbs = [
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Secretaria'],
            ['label' => 'Empresas Parceiras', 'url' => route('empresas.index')],
        ];
        
        $this->permiteGrid = false;
        
        // Usa a propriedade que já existe na trait, e setamos como 'lista' (padrão do seu x-table)
        $this->modoExibicao = 'lista'; 
    }

    public function updatingFiltroBusca()
    {
        $this->resetPage();
    }

    public function limparFiltros()
    {
        $this->reset(['filtro_busca']);
        $this->resetPage();
    }

    public function getHeadersProperty()
    {
        return [
            ['key' => 'id', 'label' => 'Cód', 'sortable' => true],
            ['key' => 'nome_fantasia', 'label' => 'Empresa Parceira', 'sortable' => true],
            ['key' => 'cnpj', 'label' => 'CNPJ', 'sortable' => true],
            ['key' => 'is_active', 'label' => 'Status', 'sortable' => true],
            ['key' => 'acoes', 'label' => 'Detalhes', 'sortable' => false, 'class' => 'text-right'],
        ];
    }

    public function render()
    {
        $query = Empresa::query();

        // Lógica de busca isolada e segura
        if (!empty($this->filtro_busca)) {
            $busca = $this->filtro_busca;
            $query->where(function($q) use ($busca) {
                $q->where('razao_social', 'ilike', '%' . $busca . '%')
                  ->orWhere('nome_fantasia', 'ilike', '%' . $busca . '%')
                  ->orWhere('cnpj', 'like', '%' . preg_replace('/\D/', '', $busca) . '%');
            });
        }

        // Ordenação segura
        $campo = $this->ordenacaoCampo ?: 'nome_fantasia';
        $direcao = $this->ordenacaoDirecao ?: 'asc';
        
        $query->orderBy($campo, $direcao);

        // Busca paginada
        $empresas = $query->withCount(['aprendizes', 'companyUsers'])->paginate($this->porPagina ?? 10);

        return view('livewire.company.empresa-manager', [
            'registros' => $empresas
        ]);
    }
}