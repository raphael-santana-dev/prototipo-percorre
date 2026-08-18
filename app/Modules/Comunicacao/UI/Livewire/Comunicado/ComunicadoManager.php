<?php

namespace App\Modules\Comunicacao\UI\Livewire\Comunicado;

use Livewire\Component;
use Livewire\WithPagination;
use App\Traits\ComPadraoListagem;
use App\Helpers\BreadcrumbHelper;
use App\Modules\Comunicacao\Domain\Models\Comunicado;

class ComunicadoManager extends Component
{
    use WithPagination, ComPadraoListagem;

    public array $breadcrumbs = [];

    public function mount()
    {
        $this->breadcrumbs = BreadcrumbHelper::generate();
        $this->permiteGrid = true;
    }

    public function getHeadersProperty()
    {
        return [
            ['key' => 'id', 'label' => '#ID', 'sortable' => true, 'class' => 'w-16'],
            ['key' => 'template_id', 'label' => 'Template Utilizado', 'sortable' => true],
            ['key' => 'destinatarios', 'label' => 'Qtd. Destinatários', 'sortable' => false],
            ['key' => 'data_agendamento', 'label' => 'Agendamento / Envio', 'sortable' => true],
            ['key' => 'status', 'label' => 'Status de Envio', 'sortable' => true],
            ['key' => 'acoes', 'label' => '', 'sortable' => false, 'class' => 'text-right w-24'],
        ];
    }

    public function excluir($id)
    {
        $comunicado = Comunicado::findOrFail($id);
        
        // Impede excluir se estiver no meio do envio
        if ($comunicado->status === 'enviando') {
            $this->dispatch('erro', msg: 'Não é possível excluir um comunicado que está sendo enviado.');
            return;
        }

        // Remove os anexos físicos se existirem
        if (is_array($comunicado->anexos)) {
            foreach ($comunicado->anexos as $anexo) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($anexo);
            }
        }

        $comunicado->delete();
        $this->dispatch('sucesso', msg: 'Comunicado excluído com sucesso!');
    }

    public function render()
    {
        $query = Comunicado::with('template');

        if ($this->ordenacaoCampo) {
            $query->orderBy($this->ordenacaoCampo, $this->ordenacaoDirecao);
        } else {
            $query->orderBy('id', 'desc');
        }

        return view('livewire.comunicacao.comunicado.comunicado-manager', [
            'registros' => $query->paginate($this->porPagina)
        ])->layout('components.layouts.app', ['title' => 'Gestão de Comunicados']);
    }
}