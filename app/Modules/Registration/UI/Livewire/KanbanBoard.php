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

    public bool $modalAntiSpamAberto = false;
    public array $conflitosAntiSpam = [];
    public array $dadosAcaoPendente = [];

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

    public function carregarMais($statusId)
    {
        $atual = $this->limitesPorColuna[$statusId] ?? 7;
        $this->limitesPorColuna[$statusId] = $atual + 10;
    }

    public function atualizarStatus($inscricaoId, $novoStatusId)
    {
        abort_if(!feature('inscricao.editar'), 403);
        $inscricao = Inscricao::find($inscricaoId);
        if ($inscricao && $inscricao->status_inscricao_id == $novoStatusId) return; 
        
        $this->verificarAntiSpam(collect([$inscricao]), $novoStatusId, false);
    }

    public function moverLote()
    {
        abort_if(!feature('inscricao.editar'), 403);
        $this->validate(['selecionados' => 'required|array|min:1', 'statusDestinoLote' => 'required|exists:status_inscricoes,id']);

        $inscricoesValidas = Inscricao::whereIn('id', $this->selecionados)->where('status_inscricao_id', '!=', $this->statusDestinoLote)->get();
        if ($inscricoesValidas->isEmpty()) {
            $this->dispatch('erro', msg: 'Todas as inscrições selecionadas já estão na coluna de destino!');
            return;
        }

        $this->verificarAntiSpam($inscricoesValidas, $this->statusDestinoLote, true);
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
        $inscricao = Inscricao::find($id);
        if ($inscricao && $inscricao->status_inscricao_id == $status) {
            $this->dispatch('erro', msg: 'O candidato já está nesta etapa!');
            return;
        }
        $this->verificarAntiSpam(collect([$inscricao]), $status, false);
    }

    // ==============================================
    // METODOS DO MOTOR ANTI-SPAM 
    // ==============================================
    private function verificarAntiSpam($inscricoesValidas, $statusId, $isLote)
    {
        $statusNovo = \App\Models\StatusInscricao::find($statusId);
        $eventoGatilho = 'inscricao.status.' . \Illuminate\Support\Str::slug($statusNovo->nome, '_');
        $automacao = \App\Modules\Comunicacao\Domain\Models\Automacao::where('evento_gatilho', $eventoGatilho)->where('status', true)->first();

        $conflitos = [];
        if ($automacao) {
            foreach ($inscricoesValidas as $insc) {
                $jaRecebeu = \App\Modules\Comunicacao\Domain\Models\Comunicado::where('template_id', $automacao->template_id)
                    ->where('inscricao_id', $insc->id)
                    ->exists();

                if ($jaRecebeu) {
                    $conflitos[] = ['id' => $insc->id, 'nome' => $insc->nome, 'email' => $insc->email];
                }
            }
        }

        $idsValidos = $inscricoesValidas->pluck('id')->toArray();

        if (count($conflitos) > 0) {
            $this->conflitosAntiSpam = $conflitos;
            $this->dadosAcaoPendente = [
                'statusId' => $statusId, 'idsOriginais' => $idsValidos, 
                'isLote' => $isLote, 'nomeStatus' => $statusNovo->nome
            ];
            $this->modalAntiSpamAberto = true;
            $this->reset(['selecionados', 'statusDestinoLote']);
            return; 
        }

        $this->executarMudancaStatusFinal($idsValidos, $statusId);
    }

    public function removerConflitoAntiSpam($idConflito)
    {
        $this->conflitosAntiSpam = array_values(array_filter($this->conflitosAntiSpam, fn($c) => $c['id'] != $idConflito));
        $this->dadosAcaoPendente['idsOriginais'] = array_values(array_diff($this->dadosAcaoPendente['idsOriginais'], [$idConflito]));

        if (empty($this->conflitosAntiSpam)) {
            if (empty($this->dadosAcaoPendente['idsOriginais'])) {
                $this->cancelarAntiSpam();
                $this->dispatch('erro', msg: 'Nenhuma inscrição restou para ser processada.');
                return;
            }
            $this->prosseguirComReenvioAntiSpam();
        }
    }

    public function prosseguirComReenvioAntiSpam()
    {
        $this->executarMudancaStatusFinal($this->dadosAcaoPendente['idsOriginais'], $this->dadosAcaoPendente['statusId']);
        $this->cancelarAntiSpam();
    }

    public function cancelarAntiSpam()
    {
        $this->modalAntiSpamAberto = false;
        $this->conflitosAntiSpam = [];
        $this->dadosAcaoPendente = [];
    }

    private function executarMudancaStatusFinal($ids, $statusId)
    {
        $statusNovo = \App\Models\StatusInscricao::find($statusId);
        $qtd = count($ids);
        
        $tracking = \App\Models\Importacao::create([
            'user_id' => auth()->id(), 'tipo' => 'inscricoes', 'operacao' => 'atualizacao_lote', 'formato' => 'system',
            'arquivo_nome' => "Alteração de Status via Fluxo: {$qtd} registros para '{$statusNovo->nome}'", 'status' => 'na_fila', 'total_linhas' => $qtd, 'linhas_processadas' => 0,
        ]);

        dispatch(new \App\Jobs\ProcessarStatusEmLoteJob($tracking->id, $ids, $statusId))->afterResponse();
        
        $this->reset(['selecionados', 'statusDestinoLote']);
        $this->dispatch('sucesso', msg: 'Ação autorizada e enviada para a Nuvem!');
    }

    public function render()
    {
        $ciclo = Ciclo::with('statusPipeline')->find($this->cicloId);
        $colunas = $ciclo ? $ciclo->statusPipeline : collect();

        // MÁGICA DE CORREÇÃO: Usando a Trait "apenasVinculosPermitidos()" para garantir o bloqueio do professor!
        $queryBase = Inscricao::where('ciclo_id', $this->cicloId)->apenasVinculosPermitidos();

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

        $totaisRaw = (clone $queryBase)
            ->selectRaw('status_inscricao_id, count(*) as total')
            ->groupBy('status_inscricao_id')
            ->pluck('total', 'status_inscricao_id');

        $totalInscricoes = $totaisRaw->sum();
        
        $inscricoesGrupadas = [];
        $resumo = [];

        foreach ($colunas as $col) {
            $totalColuna = $totaisRaw[$col->id] ?? 0;
            
            $resumo[$col->id] = [
                'nome' => $col->nome,
                'total' => $totalColuna
            ];

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