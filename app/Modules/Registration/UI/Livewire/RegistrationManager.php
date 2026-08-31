<?php

namespace App\Modules\Registration\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\On; 
use App\Models\Inscricao;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Livewire\WithPagination;
use App\Traits\ComPadraoListagem;
use Illuminate\Support\Str;
use App\Helpers\BreadcrumbHelper;
use Illuminate\Support\Facades\Auth;

#[Layout('components.layouts.app')]
#[Title('Gerenciar Inscrições - Administrativo')]
class RegistrationManager extends Component
{
    public bool $showModal = false;

    use WithPagination;
    use ComPadraoListagem;

    // Filtros
    public $filtroNome = '';
    public $filtroStatus = '';
    public $filtroCiclo = ''; 
    public $filtroUnidade = '';
    public $filtroTurno = '';
    public $filtroCurso = '';

    public $inscricaoSelecionada = null;

    // Variáveis de Lote
    public array $selecionadas = []; 
    public bool $modalLoteAberto = false;
    public $novoStatusId = '';

    public bool $modalSelecaoAvancadaAberto = false;
    public int $selecaoQtd = 40;
    public string $selecaoBase = 'pontuacao';
    public string $selecaoModo = 'global';
    public bool $selecaoPreencherVagas = false;

    public array $breadcrumbs = [];
    
    public function mount()
    {
        abort_if(!feature('inscricao.listar'), 403, 'O módulo de inscrições está desativado.');
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('inscricao.listar'), 403, 'Acesso restrito.');

        $this->breadcrumbs = [
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Processos Seletivos'],
            ['label' => 'Inscrições', 'url' => route('inscricoes.index')],
        ];

        $this->permiteGrid = true;
    }

    public function excluirInscricao($id)
    {
        abort_if(!feature('inscricao.excluir'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('inscricao.excluir'), 403);

        $inscricao = Inscricao::findOrFail($id);
        $inscricao->delete();

        // Se a inscrição excluída estivesse na lista de seleções em lote, removemos ela de lá
        if (($key = array_search($id, $this->selecionadas)) !== false) {
            unset($this->selecionadas[$key]);
        }

        $this->dispatch('sucesso', msg: 'Inscrição removida permanentemente com sucesso!');
    }

    public function updating($nomePropriedade)
    {
        if (in_array($nomePropriedade, ['filtroUnidade', 'filtroTurno', 'filtroCurso', 'filtroCiclo', 'filtroNome', 'filtroStatus'])) {
            $this->resetPage();
            $this->desmarcarTodas(); 
        }
    }

    protected function obterQueryFiltrada()
    {
        $query = Inscricao::with(['curso', 'unidade', 'turno', 'ciclo', 'statusInscricao']);
        
        if (!empty($this->filtroNome)) {
            $query->where(function($q) {
                $q->where('nome', 'ilike', '%' . $this->filtroNome . '%')
                  ->orWhere('cpf', 'like', '%' . $this->filtroNome . '%');
            });
        }
        if (!empty($this->filtroStatus)) $query->where('status_inscricao_id', $this->filtroStatus);
        if (!empty($this->filtroUnidade)) $query->where('unidade_id', $this->filtroUnidade);
        if (!empty($this->filtroTurno)) $query->where('turno_id', $this->filtroTurno);
        if (!empty($this->filtroCurso)) $query->where('curso_id', $this->filtroCurso);
        if (!empty($this->filtroCiclo)) $query->where('ciclo_id', $this->filtroCiclo);

        return $query; 
    }

    // ==========================================
    // QUICK VIEW COM AÇÃO DE STATUS
    // ==========================================
    
    #[On('quick-change-status')]
    public function alterarStatusQuickView($id, $status)
    {
        $inscricao = Inscricao::find($id);
        if ($inscricao) {
            
            // Passa a inscrição como um array para o Helper processar
            $this->aplicarMudancaDeStatus([$inscricao], $status);
            
            $this->dispatch('sucesso', msg: 'Status do candidato atualizado!');
            
            // Recarrega o próprio Quick View na hora para mostrar os botões novos selecionados
            $this->showQuickView($id);
        }
    }

    public function showQuickView(int $id)
    {
        $inscricao = Inscricao::with(['curso', 'unidade', 'turno', 'statusInscricao'])->findOrFail($id);
        
        // 1. Constrói os botões dinâmicos de troca de status usando HTML e AlpineJS
        $statusDisponiveis = \App\Models\StatusInscricao::orderBy('nome')->get();
        $botoesAcao = '<div class="flex flex-wrap gap-2 mt-2">';
        
        foreach($statusDisponiveis as $st) {
            $corClass = $st->id == $inscricao->status_inscricao_id 
                        ? 'bg-purpura-500 text-white border-purpura-500' 
                        : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700';
                        
            $botoesAcao .= '<button @click="$dispatch(\'quick-change-status\', { id: '.$id.', status: '.$st->id.' })" class="px-3 py-1.5 text-[11px] uppercase font-bold border rounded shadow-sm transition-colors '.$corClass.'">'.$st->nome.'</button>';
        }
        $botoesAcao .= '</div>';

        // 2. Constrói a tabela visual das respostas dinâmicas
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

        // 3. Dispara para o Drawer Global
        $this->dispatch('load-quick-view', [
            'title' => $inscricao->nome,
            'subtitle' => 'CPF: ' . $inscricao->cpf . ' • ' . $inscricao->email,
            'icon' => 'ph-user-focus',
            'data' => [
                'Status do Candidato' => $botoesAcao,
                'Interesse Acadêmico' => '<div class="text-sm"><b>Unidade:</b> '.($inscricao->unidade->nome ?? '-').'<br><b>Curso:</b> '.($inscricao->curso->nome ?? '-').'<br><b>Turno:</b> '.($inscricao->turno->nome ?? '-').'</div>',
                'Informações do Formulário' => $detalhesDinamicos,
                'Pontuação' => '<span class="font-bold text-purpura-600 text-2xl">'.$inscricao->pontuacao_total.' pts</span>',
                'Ações Extras' => '<a href="'.route('inscricoes.show', $inscricao->id).'" class="font-bold text-purpura-600 hover:underline">Ver Auditoria Completa</a>'
            ]
        ]);
    }

    public function selecionarQuantidade($quantidade)
    {
        // 1. Limpa qualquer seleção anterior para não acumular sujeira
        $this->desmarcarTodas();
        
        $query = $this->obterQueryFiltrada();
        
        $temRanking = (clone $query)->whereNotNull('posicao_ranking_geral')->exists();
        $temPontuacao = (clone $query)->where('pontuacao_total', '>', 0)->exists();

        // 2. Aplica a ordenação forçando a exclusão de campos nulos/vazios do topo
        if ($temRanking) {
            $query->whereNotNull('posicao_ranking_geral')
                  ->orderBy('posicao_ranking_geral', 'asc');
        } elseif ($temPontuacao) {
            $query->where('pontuacao_total', '>', 0)
                  ->orderBy('pontuacao_total', 'desc')
                  ->orderBy('created_at', 'asc');
        } else {
            $query->orderBy('id', 'asc');
        }

        // 3. Usa take() com cast inteiro rigoroso para garantir o limite no banco
        $this->selecionadas = $query->take((int) $quantidade)
                                    ->pluck('id')
                                    ->map(fn($id) => (string) $id)
                                    ->toArray();
                                    
        $this->dispatch('sucesso', msg: count($this->selecionadas) . ' inscrições selecionadas com base no critério do topo.');
    }

    public function abrirModalSelecaoAvancada()
    {
        $this->modalSelecaoAvancadaAberto = true;
    }

    public function executarSelecaoAvancada()
    {
        $this->validate(['selecaoQtd' => 'required|integer|min:1']);
        $idsSelecionados = [];

        if ($this->selecaoPreencherVagas) {
            // Busca as vagas ofertadas no ciclo filtrado (ou todos ativos)
            $queryOfertas = \App\Models\OfertaVaga::query();
            if (!empty($this->filtroCiclo)) $queryOfertas->where('ciclo_id', $this->filtroCiclo);
            else $queryOfertas->whereIn('ciclo_id', \App\Models\Ciclo::where('status', true)->pluck('id'));

            foreach ($queryOfertas->get() as $oferta) {
                if ($oferta->vagas <= 0) continue;

                $queryInsc = $this->obterQueryFiltrada()
                    ->where('ciclo_id', $oferta->ciclo_id)
                    ->where('unidade_id', $oferta->unidade_id)
                    ->where('curso_id', $oferta->curso_id)
                    ->where('turno_id', $oferta->turno_id)
                    ->whereNotIn('id', $idsSelecionados);

                if ((clone $queryInsc)->whereNotNull('posicao_ranking')->exists()) {
                    $queryInsc->orderByRaw('posicao_ranking ASC NULLS LAST');
                } else {
                    $queryInsc->orderBy('pontuacao_total', 'desc')->orderBy('created_at', 'asc');
                }

                $ids = $queryInsc->limit($oferta->vagas)->pluck('id')->toArray();
                $idsSelecionados = array_merge($idsSelecionados, $ids);
            }
        } else {
            if ($this->selecaoModo === 'global') {
                $query = $this->obterQueryFiltrada();
                
                if ($this->selecaoBase === 'ranking_geral') $query->orderByRaw('posicao_ranking_geral ASC NULLS LAST');
                elseif ($this->selecaoBase === 'ranking_turma') $query->orderByRaw('posicao_ranking ASC NULLS LAST');
                else $query->orderBy('pontuacao_total', 'desc')->orderBy('created_at', 'asc');
                
                $idsSelecionados = $query->limit($this->selecaoQtd)->pluck('id')->toArray();
            } else {
                $agrupadas = $this->obterQueryFiltrada()->get()->groupBy(function($item) {
                    return $item->unidade_id . '-' . $item->curso_id . '-' . $item->turno_id;
                });

                foreach ($agrupadas as $grupo) {
                    if ($this->selecaoBase === 'ranking_geral') $grupo = $grupo->sortBy('posicao_ranking_geral');
                    elseif ($this->selecaoBase === 'ranking_turma') $grupo = $grupo->sortBy('posicao_ranking');
                    else $grupo = $grupo->sortByDesc('pontuacao_total');

                    $ids = $grupo->take($this->selecaoQtd)->pluck('id')->toArray();
                    $idsSelecionados = array_merge($idsSelecionados, $ids);
                }
            }
        }

        $this->selecionadas = array_values(array_unique(array_map('strval', $idsSelecionados)));
        $this->modalSelecaoAvancadaAberto = false;
        $this->dispatch('sucesso', msg: count($this->selecionadas) . ' inscrições capturadas com as regras avançadas.');
    }

    public function desmarcarTodas() { $this->selecionadas = []; }

    public function abrirModalLote()
    {
        if (count($this->selecionadas) === 0) return;
        $this->novoStatusId = '';
        $this->modalLoteAberto = true;
    }

    public function salvarStatusEmLote()
    {
        abort_if(!feature('inscricao.editar'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('inscricao.editar'), 403);

        $this->validate(['novoStatusId' => 'required', 'selecionadas' => 'required|array|min:1']);
        
        $inscricoes = Inscricao::whereIn('id', $this->selecionadas)->get();
        $this->aplicarMudancaDeStatus($inscricoes, $this->novoStatusId);
        
        $this->modalLoteAberto = false;
        $this->desmarcarTodas(); 
        $this->dispatch('sucesso', msg: 'Status alterado em lote com sucesso!');
    }

    public function getFabActionsProperty()
    {
        return [
            [
                'label' => 'Alterar em Lote',
                'icon' => 'ph ph-check-square-offset',
                'wire_click' => 'abrirModalLote',
                'always_show_label' => true,
                'bg_color' => 'bg-green-500 hover:bg-green-600',
                'icon_color' => 'text-black'
            ],
            [
                'label' => 'Gerar Rankings',
                'icon' => 'ph ph-medal',
                'wire_click' => 'gerarRankingGlobal', // Botão que faltava
                'always_show_label' => true,
                'bg_color' => 'bg-indigo-500 hover:bg-indigo-600',
                'icon_color' => 'text-white',
                'confirm' => 'Gerar a posição de ranking cruzado para todas as inscrições ativas? O motor analisará Unidade, Curso e Turno em segundo plano.'
            ],
            [
                'label' => 'Recalcular Scores',
                'icon' => 'ph ph-calculator',
                'wire_click' => 'recalcularScoresGlobais',
                'always_show_label' => true,
                'bg_color' => 'bg-orange-500 hover:bg-orange-600',
                'icon_color' => 'text-white',
                'confirm' => 'Processar as pontuações e regras Multiplicadoras de TODAS as inscrições? Essa ação rodará na nuvem.'
            ]
        ];
    }

    public function getInscricoesModal()
    {
        if (!$this->modalLoteAberto || empty($this->selecionadas)) {
            return collect();
        }
        return Inscricao::with(['curso', 'unidade', 'statusInscricao'])
            ->whereIn('id', $this->selecionadas)
            ->get();
    }

    public function desmarcarIndividual($id)
    {
        if (($key = array_search((string)$id, $this->selecionadas)) !== false || ($key = array_search((int)$id, $this->selecionadas)) !== false) {
            unset($this->selecionadas[$key]);
            $this->selecionadas = array_values($this->selecionadas); // Reindexa o array
        }
        
        // Se removeu o último, fecha o modal sozinho
        if (count($this->selecionadas) === 0) {
            $this->modalLoteAberto = false;
        }
    }

    public function alterarStatusLoteRapido($statusId)
    {
        abort_if(!feature('inscricao.editar'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('inscricao.editar'), 403);

        if (count($this->selecionadas) === 0) return;
        
        $inscricoes = Inscricao::whereIn('id', $this->selecionadas)->get();
        $this->aplicarMudancaDeStatus($inscricoes, $statusId);
        
        $this->desmarcarTodas();
        $this->modalLoteAberto = false; // <-- Agora fecha o modal
        $this->dispatch('sucesso', msg: 'Status alterado rapidamente com sucesso!');
    }

    public function getHeadersProperty()
    {
        return [
            ['key' => 'checkbox', 'label' => '', 'sortable' => false, 'class' => 'w-10 text-center'],
            ['key' => 'id', 'label' => 'ID', 'sortable' => true],
            ['key' => 'nome', 'label' => 'Candidato', 'sortable' => true],
            ['key' => 'origem', 'label' => 'Origem', 'sortable' => true, 'class' => 'text-center'],
            ['key' => 'curso_id', 'label' => 'Curso', 'sortable' => false],
            ['key' => 'etapa_atual', 'label' => 'Etapa', 'sortable' => true],
            ['key' => 'pontuacao_total', 'label' => 'Score', 'sortable' => true, 'class' => 'text-center'],
            ['key' => 'posicao_ranking_geral', 'label' => 'R. Geral', 'sortable' => true, 'class' => 'text-center'],
            ['key' => 'posicao_ranking_unidade', 'label' => 'R. Unidade', 'sortable' => true, 'class' => 'text-center'],
            ['key' => 'posicao_ranking_curso', 'label' => 'R. Curso', 'sortable' => true, 'class' => 'text-center'],
            ['key' => 'posicao_ranking', 'label' => 'R. Turma', 'sortable' => true, 'class' => 'text-center'],
            ['key' => 'status', 'label' => 'Status', 'sortable' => false],
            ['key' => 'acoes', 'label' => 'Ações', 'sortable' => false, 'class' => 'text-right'],
        ];
    }

    public function recalcularScoresGlobais()
    {
        abort_if(!feature('inscricao.editar'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('inscricao.editar'), 403);
        
        $trackingScore = \App\Models\Importacao::create([
            'user_id' => auth()->id(), 'tipo' => 'inscricoes', 'operacao' => 'recalculo', 'formato' => 'system',
            'arquivo_nome' => '1/2: Recálculo Global de Scores', 'status' => 'na_fila', 'total_linhas' => 0, 'linhas_processadas' => 0,
        ]);

        $trackingRank = \App\Models\Importacao::create([
            'user_id' => auth()->id(), 'tipo' => 'inscricoes', 'operacao' => 'ranking', 'formato' => 'system',
            'arquivo_nome' => '2/2: Geração de Ranking Global', 'status' => 'na_fila', 'total_linhas' => 0, 'linhas_processadas' => 0,
        ]);

        // Encadeia os Jobs: O Ranking só inicia automaticamente após o Recálculo terminar com sucesso
        \Illuminate\Support\Facades\Bus::chain([
            new \App\Jobs\RecalcularPontuacoesGlobaisJob($trackingScore->id),
            new \App\Jobs\GerarRankingGlobalJob($trackingRank->id)
        ])->dispatch();
        
        $this->dispatch('sucesso', msg: "Processamento em cascata iniciado! Acompanhe as duas etapas no Gerenciador de Integrações.");
    }

    public function gerarRankingGlobal()
    {
        abort_if(!feature('inscricao.editar'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('inscricao.editar'), 403);

        // 1. Cria o Rastreio na Nuvem para o usuário acompanhar a barra de progresso
        $tracking = \App\Models\Importacao::create([
            'user_id' => auth()->id(),
            'tipo' => 'inscricoes',
            'operacao' => 'ranking',
            'formato' => 'system',
            'arquivo_nome' => 'Geração de Ranking Global (Job)',
            'status' => 'na_fila',
            'total_linhas' => 0,
            'linhas_processadas' => 0,
        ]);

        // 2. Dispara a fila
        dispatch(new \App\Jobs\GerarRankingGlobalJob($tracking->id))->afterResponse();

        $this->dispatch('sucesso', msg: "O motor de Ranking foi iniciado. Acompanhe a barra de progresso no Gerenciador de Integrações (I/O).");
    }

    public function limparFiltros()
    {
        $this->reset(['filtroNome', 'filtroStatus', 'filtroCiclo', 'filtroUnidade', 'filtroTurno', 'filtroCurso']);
        $this->resetPage();
    }

    /**
     * Helper responsável por processar a mudança de status
     * e criar o Estudante caso seja uma aprovação.
     */
    private function aplicarMudancaDeStatus($inscricoes, $statusId)
    {
        $statusNovo = \App\Models\StatusInscricao::find($statusId);
        if (!$statusNovo) return;

        // Limpa o nome do status para facilitar a validação
        $nomeStatus = strtolower(trim($statusNovo->nome));
        $isAprovacao = in_array($nomeStatus, ['aprovado', 'selecionado', 'aprovada']);

        foreach ($inscricoes as $inscricao) {
            // REGRA DE NEGÓCIO: Criação do Estudante
            if ($isAprovacao && !$inscricao->student_id) {
                $estudante = \App\Modules\Student\Domain\Models\Student::firstOrCreate(
                    ['email' => $inscricao->email],
                    [
                        'name' => $inscricao->nome,
                        'password' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(12)),
                        'is_active' => true,
                        'unidade_id' => $inscricao->unidade_id,
                        'cpf' => $inscricao->cpf,
                        'slug' => \Illuminate\Support\Str::slug($inscricao->nome)
                    ]
                );
                $inscricao->student_id = $estudante->id;
            }
            
            // Atualiza o status e salva a inscrição
            $inscricao->status_inscricao_id = $statusId;
            $inscricao->save();

            // LÓGICA DINÂMICA DE GATILHOS PARA E-MAIL
            $eventoGatilho = 'inscricao.pendente'; // Padrão
            
            if ($isAprovacao) {
                $eventoGatilho = 'inscricao.aprovada';
            } elseif (in_array($nomeStatus, ['reprovado', 'reprovada', 'cancelado', 'cancelada'])) {
                $eventoGatilho = 'inscricao.reprovada';
            }

            // Agora dispara a automação correta baseada no status!
            \App\Modules\Comunicacao\Services\AutomacaoService::disparar($eventoGatilho, $inscricao);
        }
    }

    public function render()
    {
        $queryBase = $this->obterQueryFiltrada()->apenasVinculosPermitidos();
        
        $metricas = [
            [
                'label' => 'Total',
                'value' => (clone $queryBase)->count(),
                'color_text' => 'text-blue-600 dark:text-blue-400',
                'color_bg' => 'bg-blue-100 dark:bg-blue-900/30',
            ],
            [
                'label' => 'Aprovados',
                'value' => (clone $queryBase)
                    ->whereHas('statusInscricao', fn ($q) => $q->where('nome', 'Aprovado'))
                    ->count(),
                'color_text' => 'text-green-600 dark:text-green-400',
                'color_bg' => 'bg-green-100 dark:bg-green-900/30',
            ],
            [
                'label' => 'Reprovados',
                'value' => (clone $queryBase)
                    ->whereHas('statusInscricao', fn ($q) => $q->where('nome', 'Reprovado'))
                    ->count(),
                'color_text' => 'text-red-600 dark:text-red-400',
                'color_bg' => 'bg-red-100 dark:bg-red-900/30',
            ],
            [
                'label' => 'Pendentes',
                'value' => (clone $queryBase)
                    ->whereHas('statusInscricao', fn ($q) => $q->whereNotIn('nome', ['Aprovado', 'Reprovado']))
                    ->count(),
                'color_text' => 'text-yellow-600 dark:text-yellow-400',
                'color_bg' => 'bg-yellow-100 dark:bg-yellow-900/30',
            ],
        ];

        if ($this->ordenacaoCampo) {
            $queryBase->orderBy($this->ordenacaoCampo, $this->ordenacaoDirecao);
        } else {
            $queryBase->orderBy('id', 'desc');
        }

        $inscricoes = $queryBase->paginate($this->porPagina);

        return view('livewire.registration.registration-manager', [
            'registros' => $inscricoes,
            'metricas' => $metricas,
            'statusInscricoesDb' => \App\Models\StatusInscricao::orderBy('nome')->get(),
            'ciclosDb' => \App\Models\Ciclo::orderBy('id', 'desc')->get(),
            'unidadesDb' => \App\Modules\Unidade\Domain\Models\Unidade::whereIn('status', ['Ativa', '1', true])->get(),
            'turnosDb' => \App\Modules\Turno\Domain\Models\Turno::orderBy('nome')->get(),
            'cursosDb' => \App\Models\Curso::whereIn('status', ['Ativo', '1', true])->get(),
        ]);
    }
}