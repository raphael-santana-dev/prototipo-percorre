<?php

namespace App\Modules\Auditoria\UI\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\ErrorLog;
use Illuminate\Support\Facades\Schema;
use App\Traits\ComPadraoListagem;

#[Layout('components.layouts.app')]
#[Title('Central de Exceções e Erros')]
class ErrorLogManager extends Component
{
    use WithPagination, ComPadraoListagem;

    public $filtroTipo = '';
    public $filtroCodigo = '';
    public $filtroBusca = '';
    public $filtroResolvido = '0'; // '0' = Pendentes/Reabertos, 'reabertos' = Apenas Reabertos, '1' = Resolvidos, '' = Todos

    public $errorDetalhes = null;
    public bool $modalDetalhesAberto = false;

    public $erroOcorrencias = null;
    public array $listaOcorrenciasModal = [];
    public bool $modalOcorrenciasAberto = false;

    public function updating($nomePropriedade)
    {
        if (in_array($nomePropriedade, ['filtroTipo', 'filtroCodigo', 'filtroBusca', 'filtroResolvido'])) {
            $this->resetPage();
        }
    }

    public function limparFiltros()
    {
        $this->reset(['filtroTipo', 'filtroCodigo', 'filtroBusca']);
        $this->filtroResolvido = '0';
        $this->resetPage();
    }

    public function verDetalhes($id)
    {
        $this->errorDetalhes = ErrorLog::with('user')->findOrFail($id);
        $this->modalDetalhesAberto = true;
    }

    public function fecharModalDetalhes()
    {
        $this->modalDetalhesAberto = false;
        $this->errorDetalhes = null;
    }

    public function verOcorrencias($id)
    {
        $log = ErrorLog::with('user')->findOrFail($id);
        $this->erroOcorrencias = $log;

        $historico = is_array($log->ocorrencias_historico) ? $log->ocorrencias_historico : [];

        if (empty($historico)) {
            $historico[] = [
                'data_hora'   => $log->created_at ? $log->created_at->format('d/m/Y H:i:s') : '-',
                'ip'          => '-',
                'user_id'     => $log->user_id,
                'user_nome'   => $log->user?->name ?? 'Convidado / Sistema',
                'user_email'  => $log->user?->email ?? '-',
                'navegador'   => '-',
                'url'         => $log->url ?? 'CLI',
                'metodo_http' => $log->metodo_http ?? 'CLI',
                'evento'      => 'Primeira ocorrência',
            ];
        }

        $this->listaOcorrenciasModal = $historico;
        $this->modalOcorrenciasAberto = true;
    }

    public function fecharModalOcorrencias()
    {
        $this->modalOcorrenciasAberto = false;
        $this->erroOcorrencias = null;
        $this->listaOcorrenciasModal = [];
    }

    public function alternarResolvido($id)
    {
        $log = ErrorLog::findOrFail($id);
        $novoEstadoResolvido = !$log->resolvido;

        $dados = ['resolvido' => $novoEstadoResolvido];
        if (Schema::hasColumn('error_logs', 'reaberto')) {
            // Se estava resolvido e foi reaberto manualmente, marca reaberto = true; se foi resolvido agora, limpa a flag reaberto
            $dados['reaberto'] = !$novoEstadoResolvido;
        }

        $log->update($dados);

        if ($this->errorDetalhes && $this->errorDetalhes->id === $log->id) {
            $this->errorDetalhes = $log->fresh(['user']);
        }

        $this->dispatch('sucesso', msg: $novoEstadoResolvido ? 'Erro marcado como resolvido!' : 'Erro reaberto para análise!');
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
        $temColunaReaberto = Schema::hasColumn('error_logs', 'reaberto');
        $temColunaUltima   = Schema::hasColumn('error_logs', 'ultima_ocorrencia_em');

        if ($this->filtroTipo !== '') {
            $query->where('tipo', $this->filtroTipo);
        }
        if ($this->filtroCodigo !== '') {
            $query->where('http_code', $this->filtroCodigo);
        }
        if ($this->filtroResolvido === 'reabertos' && $temColunaReaberto) {
            $query->where('resolvido', false)->where('reaberto', true);
        } elseif ($this->filtroResolvido !== '') {
            $query->where('resolvido', (bool) $this->filtroResolvido);
        }

        if ($this->filtroBusca !== '') {
            $query->where(function ($q) {
                $q->where('mensagem', 'ilike', '%' . $this->filtroBusca . '%')
                  ->orWhere('arquivo', 'ilike', '%' . $this->filtroBusca . '%');
            });
        }

        if ($temColunaUltima) {
            $query->orderByRaw('COALESCE(ultima_ocorrencia_em, created_at) DESC')->orderBy('id', 'desc');
        } else {
            $query->orderBy('id', 'desc');
        }

        $porPaginaEfetivo = property_exists($this, 'porPagina') ? $this->porPagina : 15;

        return view('livewire.auditoria.error-log-manager', [
            'logs' => $query->paginate($porPaginaEfetivo),
        ]);
    }
}