<?php

namespace App\Modules\Registration\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\On; 
use App\Models\Inscricao;
use Livewire\WithPagination;
use App\Traits\ComPadraoListagem;
use Illuminate\Support\Str;
use App\Helpers\BreadcrumbHelper;

#[Layout('components.layouts.app')]
#[Title('Gerenciar Inscrições - Administrativo')]
class RegistrationManager extends Component
{
    use WithPagination;
    use ComPadraoListagem;

    // Filtros
    public $filtroNome = '';
    public $filtroStatus = '';
    public $filtroCiclo = ''; 
    public $filtroUnidade = '';
    public $filtroTurno = '';
    public $filtroCurso = '';

    // Variáveis de Lote e Modais
    public array $selecionadas = []; 
    public bool $modalLoteAberto = false;
    public $novoStatusId = '';

    public bool $modalSelecaoAvancadaAberto = false;
    public int $selecaoQtd = 40;
    public string $selecaoBase = 'pontuacao';
    public string $selecaoModo = 'global';
    public bool $selecaoPreencherVagas = false;

    public array $breadcrumbs = [];

    public $nome, $cpf, $email, $celular, $ciclo_id;
    public $modalAberto = false; 
    
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

    public function fecharModal()
    {
        $this->modalAberto = false;
        $this->resetInputFields();
    }

    private function resetInputFields()
    {
        $this->nome = '';
        $this->cpf = '';
        $this->email = '';
        $this->celular = '';
        $this->ciclo_id = null;
        $this->resetErrorBag();
    }

    public function abrirModal() {
        abort_if(!feature('inscricao.criar'), 403, 'O módulo de cadastro de inscrição está temporariamente desativado no sistema.');
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('inscricao.criar'), 403, 'Sem permissão');

        $this->resetValidation();
        $this->reset(['nome', 'cpf', 'email', 'celular', 'ciclo_id']);

        $this->modalAberto = true;
    }

    public function salvarNovaInscricao()
    {
        abort_if(!feature('inscricao.criar'), 403);

        $this->validate([
            'nome' => 'required|string|min:3',
            'cpf' => 'required|string|min:11',
            'email' => 'required|email',
            'celular' => 'nullable|string',
            'ciclo_id' => 'required|exists:ciclos,id'
        ]);

        $cpfLimpo = preg_replace('/[^0-9]/', '', $this->cpf);

        // Verifica se CPF já existe
        if (Inscricao::where('cpf', $cpfLimpo)->exists()) {
            $this->addError('cpf', 'Este CPF já está cadastrado no sistema.');
            return;
        }

        $payload = [
            'nome' => $this->nome,
            'cpf' => $cpfLimpo,
            'email' => $this->email,
            'celular' => $this->celular,
            'ciclo_id' => $this->ciclo_id,
            'origem' => 'manual',
            'etapa_atual' => 1,
            'status_inscricao_id' => 1, // Status Pendente por padrão
            'criado_por' => auth()->id() // Rastro de auditoria manual
        ];

        // MÁGICA: Verifica se tem a permissão direta
        $temPermissaoTotal = auth()->user()->hasRole('dev') || auth()->user()->can('inscricao.criar_direto');

        if ($temPermissaoTotal) {
            // CADASTRO IMEDIATO
            $inscricao = Inscricao::create($payload);
            
            // Dispara E-mail com Link de Retomada (Usando template)
            $linkRetomada = route('inscricao.retomar', \Illuminate\Support\Facades\Crypt::encrypt($inscricao->id));
            \Illuminate\Support\Facades\Mail::to($inscricao->email)->send(new \App\Mail\TemplateGenericoMail('boas_vindas_estudante', $inscricao->toArray(), $linkRetomada));

            $this->dispatch('sucesso', msg: 'Inscrição efetuada! E-mail de retomada enviado ao estudante.');
        } else {
            // ENTRA NO FLUXO DE APROVAÇÃO (HELP DESK)
            $solicitacao = \App\Models\Solicitacao::create([
                'tema' => 'cadastro_nova_inscricao',
                'solicitante_type' => \App\Models\User::class,
                'solicitante_id' => auth()->id(),
                'justificativa' => "Cadastro inserido por " . auth()->user()->name . ". Aguardando análise para liberação.",
                'status' => 'pendente',
                'payload' => $payload
            ]);

            // Busca e-mail do sistema nas configs para notificar o admin
            $emailSistema = \App\Models\ConfiguracaoGeral::where('chave', 'email_sistema')->value('valor') ?? 'admin@percorre.com';
            \Illuminate\Support\Facades\Mail::to($emailSistema)->send(new \App\Mail\NovaSolicitacaoMail($solicitacao, auth()->user()->name, 'Aprovação de Nova Inscrição'));

            $this->dispatch('sucesso', msg: 'Sua solicitação de cadastro foi enviada para análise da administração.');
        }

        $this->fecharModal();
    }

    public function excluirInscricao($id)
    {
        abort_if(!feature('inscricao.excluir'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('inscricao.excluir'), 403);

        Inscricao::findOrFail($id)->delete();

        if (($key = array_search((string)$id, $this->selecionadas)) !== false || ($key = array_search((int)$id, $this->selecionadas)) !== false) {
            unset($this->selecionadas[$key]);
            $this->selecionadas = array_values($this->selecionadas);
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

    #[On('quick-change-status')]
    public function alterarStatusQuickView($id, $statusId)
    {
        abort_if(!feature('inscricao.editar'), 403);
        
        // Manda o ID único pra fila e deixa o Worker criar conta/mandar email
        $tracking = \App\Models\Importacao::create([
            'user_id' => auth()->id(),
            'tipo' => 'inscricoes',
            'operacao' => 'atualizacao_lote',
            'formato' => 'system',
            'arquivo_nome' => "Alteração Individual via QuickView",
            'status' => 'na_fila',
            'total_linhas' => 1,
            'linhas_processadas' => 0,
        ]);

        dispatch(new \App\Jobs\ProcessarStatusEmLoteJob($tracking->id, [$id], $statusId))->afterResponse();
        
        $this->dispatch('sucesso', msg: 'Status do candidato enviado para processamento!');
        $this->showQuickView($id);
    }

    public function showQuickView(int $id)
    {
        $inscricao = Inscricao::with(['curso', 'unidade', 'turno', 'statusInscricao'])->findOrFail($id);
        
        $statusDisponiveis = \App\Models\StatusInscricao::orderBy('nome')->get();
        $botoesAcao = '<div class="flex flex-wrap gap-2 mt-2">';
        
        foreach($statusDisponiveis as $st) {
            $corClass = $st->id == $inscricao->status_inscricao_id 
                        ? 'bg-purpura-500 text-white border-purpura-500' 
                        : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700';
                        
            $botoesAcao .= '<button @click="$dispatch(\'quick-change-status\', { id: '.$id.', statusId: '.$st->id.' })" class="px-3 py-1.5 text-[11px] uppercase font-bold border rounded shadow-sm transition-colors '.$corClass.'">'.$st->nome.'</button>';
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
        $this->desmarcarTodas();
        
        $query = $this->obterQueryFiltrada();
        $temRanking = (clone $query)->whereNotNull('posicao_ranking_geral')->exists();
        $temPontuacao = (clone $query)->where('pontuacao_total', '>', 0)->exists();

        if ($temRanking) {
            $this->ordenacaoCampo = 'posicao_ranking_geral';
            $this->ordenacaoDirecao = 'asc';
            $query->whereNotNull('posicao_ranking_geral')->orderByRaw('posicao_ranking_geral ASC NULLS LAST');
        } elseif ($temPontuacao) {
            $this->ordenacaoCampo = 'pontuacao_total';
            $this->ordenacaoDirecao = 'desc';
            $query->where('pontuacao_total', '>', 0)->orderBy('pontuacao_total', 'desc')->orderBy('created_at', 'asc');
        } else {
            $this->ordenacaoCampo = 'id';
            $this->ordenacaoDirecao = 'asc';
            $query->orderBy('id', 'asc');
        }

        $this->resetPage();

        $this->selecionadas = $query->take((int) $quantidade)
                                    ->pluck('id')
                                    ->map(fn($id) => (string) $id)
                                    ->toArray();
                                    
        $this->dispatch('sucesso', msg: count($this->selecionadas) . ' inscrições selecionadas e a tabela foi reordenada.');
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
                } elseif ((clone $queryInsc)->where('pontuacao_total', '>', 0)->exists()) {
                    $queryInsc->orderBy('pontuacao_total', 'desc')->orderBy('created_at', 'asc');
                } else {
                    $queryInsc->orderBy('id', 'asc');
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
            $this->selecionadas = array_values($this->selecionadas);
        }
        if (count($this->selecionadas) === 0) {
            $this->modalLoteAberto = false;
        }
    }

    public function alterarStatusLoteRapido($statusId)
    {
        abort_if(!feature('inscricao.editar'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('inscricao.editar'), 403);

        if (count($this->selecionadas) === 0) return;
        
        $statusNovo = \App\Models\StatusInscricao::find($statusId);
        $qtd = count($this->selecionadas);

        $tracking = \App\Models\Importacao::create([
            'user_id' => auth()->id(),
            'tipo' => 'inscricoes',
            'operacao' => 'atualizacao_lote',
            'formato' => 'system',
            'arquivo_nome' => "Alteração em Lote: {$qtd} registros para '{$statusNovo->nome}'",
            'status' => 'na_fila',
            'total_linhas' => $qtd,
            'linhas_processadas' => 0,
        ]);

        dispatch(new \App\Jobs\ProcessarStatusEmLoteJob($tracking->id, $this->selecionadas, $statusId))->afterResponse();
        
        $this->desmarcarTodas();
        $this->modalLoteAberto = false;
        
        $this->dispatch('sucesso', msg: 'Ação enviada para a Nuvem! Acompanhe o progresso no Gerenciador de Integrações.');
    }

    public function salvarStatusEmLote()
    {
        $this->validate(['novoStatusId' => 'required', 'selecionadas' => 'required|array|min:1']);
        $this->alterarStatusLoteRapido($this->novoStatusId);
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

        $tracking = \App\Models\Importacao::create([
            'user_id' => auth()->id(), 'tipo' => 'inscricoes', 'operacao' => 'ranking', 'formato' => 'system',
            'arquivo_nome' => 'Geração de Ranking Global (Job)', 'status' => 'na_fila', 'total_linhas' => 0, 'linhas_processadas' => 0,
        ]);

        dispatch(new \App\Jobs\GerarRankingGlobalJob($tracking->id))->afterResponse();

        $this->dispatch('sucesso', msg: "O motor de Ranking foi iniciado. Acompanhe a barra de progresso no Gerenciador de Integrações (I/O).");
    }

    public function limparFiltros()
    {
        $this->reset(['filtroNome', 'filtroStatus', 'filtroCiclo', 'filtroUnidade', 'filtroTurno', 'filtroCurso']);
        $this->resetPage();
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
                'wire_click' => 'gerarRankingGlobal',
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

    public function avancarSelecionadas()
    {
        abort_if(!feature('inscricao.editar'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('inscricao.editar'), 403);

        if (count($this->selecionadas) === 0) {
            $this->dispatch('erro', msg: 'Selecione pelo menos uma inscrição para avançar.');
            return;
        }
        
        $qtd = count($this->selecionadas);

        $tracking = \App\Models\Importacao::create([
            'user_id' => auth()->id(),
            'tipo' => 'inscricoes',
            'operacao' => 'atualizacao_lote',
            'formato' => 'system',
            'arquivo_nome' => "Avanço de Funil em Lote: {$qtd} registros",
            'status' => 'na_fila',
            'total_linhas' => $qtd,
            'linhas_processadas' => 0,
        ]);

        dispatch(new \App\Jobs\AvancarStatusNoFunilJob($tracking->id, $this->selecionadas))->afterResponse();
        
        $this->desmarcarTodas();
        $this->dispatch('sucesso', msg: 'Avanço iniciado em background!');
    }

    public function render()
    {
        $queryBase = $this->obterQueryFiltrada()->apenasVinculosPermitidos();
        
        $metricas = [
            ['label' => 'Total', 'value' => (clone $queryBase)->count(), 'color_text' => 'text-blue-600 dark:text-blue-400', 'color_bg' => 'bg-blue-100 dark:bg-blue-900/30'],
            ['label' => 'Aprovados', 'value' => (clone $queryBase)->whereHas('statusInscricao', fn ($q) => $q->where('nome', 'Aprovado'))->count(), 'color_text' => 'text-green-600 dark:text-green-400', 'color_bg' => 'bg-green-100 dark:bg-green-900/30'],
            ['label' => 'Reprovados', 'value' => (clone $queryBase)->whereHas('statusInscricao', fn ($q) => $q->where('nome', 'Reprovado'))->count(), 'color_text' => 'text-red-600 dark:text-red-400', 'color_bg' => 'bg-red-100 dark:bg-red-900/30'],
            ['label' => 'Pendentes', 'value' => (clone $queryBase)->whereHas('statusInscricao', fn ($q) => $q->whereNotIn('nome', ['Aprovado', 'Reprovado']))->count(), 'color_text' => 'text-yellow-600 dark:text-yellow-400', 'color_bg' => 'bg-yellow-100 dark:bg-yellow-900/30'],
        ];

        if ($this->ordenacaoCampo) {
            if (in_array($this->ordenacaoCampo, ['posicao_ranking_geral', 'posicao_ranking_unidade', 'posicao_ranking_curso', 'posicao_ranking'])) {
                $queryBase->orderByRaw("{$this->ordenacaoCampo} {$this->ordenacaoDirecao} NULLS LAST");
            } else {
                $queryBase->orderBy($this->ordenacaoCampo, $this->ordenacaoDirecao);
            }
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