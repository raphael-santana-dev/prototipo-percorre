<?php

namespace App\Modules\Registration\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\On;
use App\Models\Ciclo;
use App\Models\Inscricao;
use App\Models\Curso;
use App\Models\StatusInscricao;
use App\Modules\Unidade\Domain\Models\Unidade;

#[Layout('components.layouts.app')]
#[Title('Fluxo de Inscrição')]
class KanbanBoard extends Component
{
    public $cicloId = null;
    public array $selecionados = []; 
    public $statusDestinoLote = '';

    // Filtros
    public $filtroBusca = '';
    public $filtroCurso = '';
    public $filtroUnidade = '';
    public $filtroDataFim = '';
    public $ordenacao = 'recentes'; 

    // Controle de Scroll Infinito
    public $limitesPorColuna = [];

    public function mount($id = null)
    {
        abort_if(!feature('crm.acessar'), 403, 'CRM Desativado.');
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('crm.acessar'), 403, 'Acesso restrito.');
        
        if ($id) {
            $this->cicloId = $id;
        } else {
            $cicloAtivo = Ciclo::where('status', true)->latest()->first();
            $this->cicloId = $cicloAtivo ? $cicloAtivo->id : null;
        }
    }

    // Zera os limites caso o usuário altere algum filtro
    public function updating($nomePropriedade)
    {
        if (in_array($nomePropriedade, ['filtroBusca', 'filtroCurso', 'filtroUnidade', 'filtroDataFim', 'ordenacao'])) {
            $this->limitesPorColuna = [];
        }
    }

    public function limparFiltros()
    {
        $this->reset(['filtroBusca', 'filtroCurso', 'filtroUnidade', 'filtroDataFim', 'ordenacao', 'limitesPorColuna']);
    }

    // Método acionado pelo AlpineJS quando o usuário chega no fim da rolagem da coluna
    public function carregarMais($statusId)
    {
        $atual = $this->limitesPorColuna[$statusId] ?? 7;
        $this->limitesPorColuna[$statusId] = $atual + 10;
    }

    public function atualizarStatus($inscricaoId, $novoStatusId)
    {
        abort_if(!feature('inscricao.editar'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('inscricao.editar'), 403);
        
        $tracking = \App\Models\Importacao::create([
            'user_id' => auth()->id(), 'tipo' => 'inscricoes', 'operacao' => 'atualizacao_lote', 'formato' => 'system',
            'arquivo_nome' => "Alteração via Fluxo (Drag/Drop)", 'status' => 'na_fila', 'total_linhas' => 1, 'linhas_processadas' => 0,
        ]);
        dispatch(new \App\Jobs\ProcessarStatusEmLoteJob($tracking->id, [$inscricaoId], $novoStatusId))->afterResponse();
    }

    public function moverLote()
    {
        abort_if(!feature('inscricao.editar'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('inscricao.editar'), 403);
        
        $this->validate([
            'selecionados' => 'required|array|min:1',
            'statusDestinoLote' => 'required|exists:status_inscricoes,id'
        ]);

        $tracking = \App\Models\Importacao::create([
            'user_id' => auth()->id(), 'tipo' => 'inscricoes', 'operacao' => 'atualizacao_lote', 'formato' => 'system',
            'arquivo_nome' => "Alteração em Lote via Fluxo", 'status' => 'na_fila', 'total_linhas' => count($this->selecionados), 'linhas_processadas' => 0,
        ]);
        dispatch(new \App\Jobs\ProcessarStatusEmLoteJob($tracking->id, $this->selecionados, $this->statusDestinoLote))->afterResponse();

        $this->reset(['selecionados', 'statusDestinoLote']);
        $this->dispatch('sucesso', msg: 'Ação enviada para processamento em background!');
    }

    public function showQuickView(int $id)
    {
        $inscricao = Inscricao::with(['curso', 'unidade', 'turno', 'statusInscricao'])->findOrFail($id);
        $statusDisponiveis = StatusInscricao::orderBy('nome')->get();
        
        $botoesAcao = '<div class="flex flex-wrap gap-2 mt-2">';
        foreach($statusDisponiveis as $st) {
            $corClass = $st->id == $inscricao->status_inscricao_id 
                        ? 'bg-purpura-500 text-white border-purpura-500' 
                        : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700';
            $botoesAcao .= '<button @click="$dispatch(\'quick-change-status-crm\', { id: '.$id.', status: '.$st->id.' })" class="px-3 py-1.5 text-[11px] uppercase font-bold border rounded shadow-sm transition-colors '.$corClass.'">'.$st->nome.'</button>';
        }
        $botoesAcao .= '</div>';

        $detalhesDinamicos = '';
        if($inscricao->dados_dinamicos) {
            $detalhesDinamicos .= '<div class="mt-2 grid grid-cols-1 gap-2">';
            foreach($inscricao->dados_dinamicos as $chave => $valor) {
                $valorFormatado = is_array($valor) ? implode(', ', $valor) : $valor;
                $detalhesDinamicos .= '<div class="bg-gray-50 dark:bg-gray-800 p-2 rounded border border-gray-100 dark:border-gray-700"><span class="block text-[10px] uppercase text-gray-500 font-bold">'.str_replace('_', ' ', $chave).'</span><span class="text-sm font-medium text-gray-900 dark:text-gray-200">'.($valorFormatado ?: '-').'</span></div>';
            }
            $detalhesDinamicos .= '</div>';
        } else {
            $detalhesDinamicos = '<span class="text-gray-500 text-sm italic">Nenhum dado complementar registrado.</span>';
        }

        $this->dispatch('load-quick-view', [
            'title' => $inscricao->nome,
            'subtitle' => 'CPF: ' . $inscricao->cpf . ' • ' . $inscricao->email,
            'icon' => 'ph-user-focus',
            'data' => [
                'Status' => $botoesAcao,
                'Interesse' => '<div class="text-sm"><b>Unidade:</b> '.($inscricao->unidade->nome ?? '-').'<br><b>Curso:</b> '.($inscricao->curso->nome ?? '-').'</div>',
                'Informações do Formulário' => $detalhesDinamicos,
                'Ações' => '<a href="'.route('inscricoes.show', $inscricao->id).'" class="font-bold text-purpura-600 hover:underline">Ver Auditoria Completa</a>'
            ]
        ]);
    }

    #[On('quick-change-status-crm')]
    public function quickChangeStatus($id, $status)
    {
        $this->atualizarStatus($id, $status);
        $this->dispatch('sucesso', msg: 'Ação enviada para processamento!');
    }

    public function render()
    {
        $ciclo = Ciclo::with('statusPipeline')->find($this->cicloId);
        $colunas = $ciclo ? $ciclo->statusPipeline : collect();

        // 1. Constrói a Query Base com os filtros
        $queryBase = Inscricao::where('ciclo_id', $this->cicloId);

        if (!empty($this->filtroBusca)) {
            $queryBase->where(function($q) {
                $q->where('nome', 'ilike', '%' . $this->filtroBusca . '%')
                  ->orWhere('cpf', 'like', '%' . $this->filtroBusca . '%');
            });
        }
        if (!empty($this->filtroCurso)) $queryBase->where('curso_id', $this->filtroCurso);
        if (!empty($this->filtroUnidade)) $queryBase->where('unidade_id', $this->filtroUnidade);
        
        if (!empty($this->filtroDataFim)) {
            $dataFim = str_replace('T', ' ', $this->filtroDataFim);
            if (strlen($dataFim) === 10) $dataFim .= ' 23:59:59';
            elseif (strlen($dataFim) === 16) $dataFim .= ':59';
            $queryBase->where('created_at', '<=', $dataFim);
        }

        // 2. Extração Rápida de Totais (Uma única query no BD usando GroupBy e Count)
        $totaisRaw = (clone $queryBase)
            ->selectRaw('status_inscricao_id, count(*) as total')
            ->groupBy('status_inscricao_id')
            ->pluck('total', 'status_inscricao_id');

        $totalInscricoes = $totaisRaw->sum();
        
        // 3. Busca Isolada por Coluna respeitando o Limite (Scroll)
        $inscricoesGrupadas = [];
        $resumo = [];

        foreach ($colunas as $col) {
            $totalColuna = $totaisRaw[$col->id] ?? 0;
            
            $resumo[$col->id] = [
                'nome' => $col->nome,
                'total' => $totalColuna
            ];

            // Define limite de 7 inicial, senão pega o limite guardado pelo scroll
            $limite = $this->limitesPorColuna[$col->id] ?? 7;

            if ($totalColuna > 0) {
                $q = (clone $queryBase)
                    ->where('status_inscricao_id', $col->id)
                    ->select('id', 'nome', 'cpf', 'curso_id', 'status_inscricao_id', 'updated_at')
                    ->with(['curso:id,nome']) 
                    ->limit($limite);
                    
                if ($this->ordenacao === 'nome_asc') {
                    $q->orderBy('nome', 'asc');
                } elseif ($this->ordenacao === 'nome_desc') {
                    $q->orderBy('nome', 'desc');
                } else {
                    $q->orderBy('updated_at', 'desc');
                }

                $inscricoesGrupadas[$col->id] = $q->get();
            } else {
                $inscricoesGrupadas[$col->id] = collect();
            }
        }

        return view('livewire.registration.kanban-board', [
            'ciclo' => $ciclo,
            'colunas' => $colunas,
            'inscricoesGrupadas' => $inscricoesGrupadas,
            'cursosDb' => Curso::select('id', 'nome')->whereIn('status', ['Ativo', '1', true])->orderBy('nome')->get(),
            'unidadesDb' => Unidade::select('id', 'nome')->whereIn('status', ['Ativa', '1', true])->orderBy('nome')->get(),
            'resumo' => $resumo,
            'totalInscricoes' => $totalInscricoes
        ]);
    }
}