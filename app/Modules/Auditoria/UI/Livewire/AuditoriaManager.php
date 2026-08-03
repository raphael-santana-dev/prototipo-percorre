<?php

namespace App\Modules\Auditoria\UI\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\AuditoriaLog;
use App\Traits\ComPadraoListagem;
use App\Helpers\BreadcrumbHelper;

class AuditoriaManager extends Component
{
    use WithPagination;
    use ComPadraoListagem;

    public function mount()
    {
        abort_if(!auth()->user()->hasRole('dev|admin'), 403);
    }

    public function getHeadersProperty()
    {
        // Headers simplificados. sortable => false desativa o clique no título da tabela.
        return [
            ['key' => 'id', 'label' => '#ID', 'sortable' => false, 'class' => 'w-16'],
            ['key' => 'created_at', 'label' => 'Data / Hora', 'sortable' => false],
            ['key' => 'usuario_nome', 'label' => 'Usuário / IP', 'sortable' => false],
            ['key' => 'acao', 'label' => 'Ação', 'sortable' => false, 'class' => 'text-center'],
            ['key' => 'tabela_alterada', 'label' => 'Tabela / Reg. ID', 'sortable' => false],
        ];
    }

    public function render()
    {
        // Consulta direta, sem filtros e com ordenação fixa (do mais novo para o mais antigo)
        $logs = AuditoriaLog::with('usuario')
            ->latest()
            ->paginate($this->porPagina);

        return view('livewire.auditoria.auditoria-manager', [
            'registros' => $logs,
            'breadcrumbs' => BreadcrumbHelper::generate(),
        ])->layout('components.layouts.app', ['title' => 'Auditoria de Sistema']);
    }
}