<?php

namespace App\Modules\Log\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Str;
use Livewire\WithPagination;
use App\Models\AuditoriaLog;
use App\Traits\ComPadraoListagem;
use App\Helpers\BreadcrumbHelper;

class LogManager extends Component {
    use WithPagination;
    use ComPadraoListagem;

    public function mount() 
    {
        abort_if(!auth()->user()->hasRole('dev|admin'), 403);
    }

    public function getHeadersProperty()
    {
        return [
            ['key' => 'id', 'label' => '#ID', 'sortable' => true, 'class' => 'w-16'],
            ['key' => 'created_at', 'label' => 'Data e Hora', 'sortable' => true],
            ['key' => 'usuario_id', 'label' => 'Usuário / Autor', 'sortable' => false],
            ['key' => 'acao', 'label' => 'Ação', 'sortable' => true, 'class' => 'text-center'],
            ['key' => 'modelo', 'label' => 'Módulo / Tabela', 'sortable' => true],
            ['key' => 'acoes', 'label' => 'Ações', 'sortable' => false, 'class' => 'text-right'],
        ];
    }

    public function render()
    {
        $query = AuditoriaLog::query();

        $query->orderBy('id', 'asc');

        $auditoriaLogs = $query->paginate($this->porPagina);

        return view('livewire.log.log-manager', [
            'registros' => $auditoriaLogs,
            'breadcrumbs' => BreadcrumbHelper::generate(),
        ]);
    }
}