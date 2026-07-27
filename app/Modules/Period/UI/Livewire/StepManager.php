<?php

namespace App\Modules\Period\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Ciclo;
use App\Models\Etapa;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Livewire\WithPagination;
use App\Traits\ComPadraoListagem;
use Illuminate\Support\Str;
use App\Helpers\BreadcrumbHelper;

class StepManager extends Component 
{
    use WithPagination; // Habilita a paginação sem recarregar a página
    use ComPadraoListagem; // Traz a ordenação e os registos por página

    public function mount() 
    {
        if (!auth()->user()->hasAnyRole(['dev'])) {
            abort(403, 'Você não tem permissão para acessar esta página.');
        }
    }

    public function getHeadersProperty()
    {
        //ID, Ordem, Nome, Status, ações
        return [
            ['key' => 'id', 'label' => 'ID', 'sortable' => true],
            ['key' => 'numero', 'label' => 'Ordem', 'sortable' => true],
            ['key' => 'nome', 'label' => 'Nome', 'sortable' => true],
            ['key' => 'acoes', 'label' => 'Ações', 'sortable' => false, 'class' => 'text-right'], // Coluna para ações
        ];
    }

    public function render()
    {
        $query = Etapa::query();

        // Aplica a mágica da ordenação da Trait
        if ($this->ordenacaoCampo) {
            $query->orderBy($this->ordenacaoCampo, $this->ordenacaoDirecao);
        } else {
            // Ordem padrão: pelo número da etapa
            $query->orderBy('numero', 'asc');
        }

        // Usa o $this->porPagina da Trait
        $etapas = $query->paginate($this->porPagina);

        return view('livewire.period.step-manager', [
            'registros' => $etapas,
            'breadcrumbs' => BreadcrumbHelper::generate()
        ])->layout('components.layouts.app', ['title' => 'Gestão de Etapas']);
    }
}