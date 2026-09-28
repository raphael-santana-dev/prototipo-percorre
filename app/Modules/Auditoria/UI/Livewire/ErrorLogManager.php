<?php

namespace App\Modules\Auditoria\UI\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\ErrorLog;

#[Layout('components.layouts.app')]
#[Title('Central de Exceções e Erros')]
class ErrorLogManager extends Component
{
    use WithPagination;

    public $filtroTipo = '';
    public $filtroCodigo = '';
    public $filtroBusca = '';
    public $filtroResolvido = '0'; // Por defeito mostra os não resolvidos

    public $errorDetalhes = null;
    public $modalDetalhesAberto = false;

    public function updating($nomePropriedade)
    {
        if (in_array($nomePropriedade, ['filtroTipo', 'filtroCodigo', 'filtroBusca', 'filtroResolvido'])) {
            $this->resetPage();
        }
    }

    public function limparFiltros()
    {
        $this->reset(['filtroTipo', 'filtroCodigo', 'filtroBusca', 'filtroResolvido']);
        $this->resetPage();
    }

    public function verDetalhes($id)
    {
        $this->errorDetalhes = ErrorLog::with('user')->findOrFail($id);
        $this->modalDetalhesAberto = true;
    }

    public function alternarResolvido($id)
    {
        $log = ErrorLog::findOrFail($id);
        $log->update(['resolvido' => !$log->resolvido]);
        $this->dispatch('sucesso', msg: 'Estado do erro atualizado com sucesso!');
    }

    public function eliminarErro($id)
    {
        ErrorLog::findOrFail($id)->delete();
        $this->dispatch('sucesso', msg: 'Registo de erro removido.');
    }

    public function limparTodos()
    {
        ErrorLog::truncate();
        $this->dispatch('sucesso', msg: 'Todos os registos de erros foram limpos.');
    }

    public function render()
    {
        $query = ErrorLog::with('user');

        if ($this->filtroTipo !== '') {
            $query->where('tipo', $this->filtroTipo);
        }
        if ($this->filtroCodigo !== '') {
            $query->where('http_code', $this->filtroCodigo);
        }
        if ($this->filtroResolvido !== '') {
            $query->where('resolvido', (bool)$this->filtroResolvido);
        }
        if ($this->filtroBusca !== '') {
            $query->where(function($q) {
                $q->where('mensagem', 'ilike', '%' . $this->filtroBusca . '%')
                  ->orWhere('arquivo', 'ilike', '%' . $this->filtroBusca . '%');
            });
        }

        $query->orderBy('id', 'desc');

        return view('livewire.auditoria.error-log-manager', [
            'logs' => $query->paginate(15)
        ]);
    }
}