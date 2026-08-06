<?php

namespace App\Modules\Registration\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\On; // IMPORTANTE PARA O QUICK VIEW
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

    public array $breadcrumbs = [];
    
    public function mount()
    {
        abort_if(!auth()->user()->hasRole('dev|admin|professor'), 403);

        $this->breadcrumbs = BreadcrumbHelper::generate();

        $this->permiteGrid = true;
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
    // ETAPA 3: QUICK VIEW COM AÇÃO DE STATUS
    // ==========================================
    
    // Este método é chamado silenciosamente pelo AlpineJS quando clicamos nos botões de status dentro do Drawer
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
            // Pinta o botão de Roxo se for o status atual do aluno
            $corClass = $st->id == $inscricao->status_inscricao_id 
                        ? 'bg-purpura-500 text-white border-purpura-500' 
                        : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700';
                        
            // Usa o $dispatch do Alpine para enviar a informação de volta pro nosso #[On] no Livewire
            $botoesAcao .= '<button @click="$dispatch(\'quick-change-status\', { id: '.$id.', status: '.$st->id.' })" class="px-3 py-1.5 text-[11px] uppercase font-bold border rounded shadow-sm transition-colors '.$corClass.'">'.$st->nome.'</button>';
        }
        $botoesAcao .= '</div>';

        // 2. Constrói a tabela visual das respostas dinâmicas
        $detalhesDinamicos = '';
        if($inscricao->dados_dinamicos) {
            $detalhesDinamicos .= '<div class="mt-2 grid grid-cols-1 gap-2">';
            foreach($inscricao->dados_dinamicos as $chave => $valor) {
                
                // MUDANÇA AQUI: Se for array (checkbox), junta com vírgula e espaço. Se não, mantém o valor.
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

    // ==========================================
    // MÉTODOS DE LOTE MANTIDOS
    // ==========================================
    public function selecionarQuantidade($quantidade)
    {
        $this->selecionadas = $this->obterQueryFiltrada()
            ->limit($quantidade)
            ->pluck('id')
            ->map(fn($id) => (string) $id) 
            ->toArray();
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
        $this->validate(['novoStatusId' => 'required', 'selecionadas' => 'required|array|min:1']);
        
        // Pega as inscrições e envia para o Helper processar as aprovações individualmente
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
                'bg_color' => 'bg-green-500 hover:bg-green-600',
                'icon_color' => 'text-white'
            ],
            [
                'label' => 'Recalcular Tudo',
                'icon' => 'ph ph-calculator',
                'wire_click' => 'recalcularScoresGlobais',
                'bg_color' => 'bg-green-500 hover:bg-green-600',
                'icon_color' => 'text-black'
            ]
        ];
    }

    public function alterarStatusLoteRapido($statusId)
    {
        if (count($this->selecionadas) === 0) return;
        
        // Pega as inscrições e envia para o Helper processar as aprovações individualmente
        $inscricoes = Inscricao::whereIn('id', $this->selecionadas)->get();
        $this->aplicarMudancaDeStatus($inscricoes, $statusId);
        
        $this->desmarcarTodas();
        $this->dispatch('sucesso', msg: 'Status alterado rapidamente com sucesso!');
    }

    public function getHeadersProperty()
    {
        return [
            ['key' => 'checkbox', 'label' => '', 'sortable' => false, 'class' => 'w-10 text-center'],
            ['key' => 'id', 'label' => 'ID', 'sortable' => true],
            ['key' => 'nome', 'label' => 'Candidato', 'sortable' => true],
            ['key' => 'curso_id', 'label' => 'Curso', 'sortable' => false],
            ['key' => 'etapa_atual', 'label' => 'Etapa', 'sortable' => true],
            ['key' => 'pontuacao_total', 'label' => 'Score / Ranking', 'sortable' => true, 'class' => 'text-center'], // COLUNA ADICIONADA
            ['key' => 'status', 'label' => 'Status', 'sortable' => false],
            ['key' => 'acoes', 'label' => 'Ações', 'sortable' => false, 'class' => 'text-right'],
        ];
    }

    public function recalcularScoresGlobais()
    {
        abort_if(!auth()->user()->hasRole('dev|admin'), 403);
        
        $atualizados = 0;
        
        // Pega APENAS os ciclos ATIVOS que têm regras definidas
        $ciclos = \App\Models\Ciclo::where('status', true)
                    ->whereNotNull('regras_pontuacao')
                    ->get();
        
        foreach ($ciclos as $ciclo) {
            $regras = is_string($ciclo->regras_pontuacao) ? json_decode($ciclo->regras_pontuacao, true) : $ciclo->regras_pontuacao;
            if (empty($regras)) continue;

            // Varre as inscrições desse ciclo de 100 em 100 (alta performance)
            $ciclo->inscricoes()->chunk(100, function ($inscricoes) use ($regras, &$atualizados) {
                foreach ($inscricoes as $inscricao) {
                    $total = 0;
                    $detalhes = ['auditoria_detalhada' => []];
                    $respostas = is_string($inscricao->dados_dinamicos) ? json_decode($inscricao->dados_dinamicos, true) : ($inscricao->dados_dinamicos ?? []);

                    foreach ($regras as $regra) {
                        $campo = trim($regra['campo'] ?? '');
                        $operador = trim($regra['operador'] ?? '=');
                        $pontos = (int) ($regra['pontos'] ?? 0);
                        $valorResposta = null;

                        if ($campo === 'idade' && $inscricao->data_nascimento) {
                            $valorResposta = \Carbon\Carbon::parse($inscricao->data_nascimento)->age;
                        } elseif (in_array($campo, ['estado', 'cidade', 'curso_id', 'turno_id', 'possui_deficiencia'])) {
                            $valorResposta = $inscricao->$campo;
                        } elseif (isset($respostas[$campo])) {
                            $valorResposta = $respostas[$campo];
                        }

                        if ($valorResposta !== null && $valorResposta !== '') {
                            $valorAlvoStr = trim((string)($regra['valor'] ?? ''));
                            $valoresEsperados = in_array($operador, ['between', 'in']) ? array_map('trim', explode(',', $valorAlvoStr)) : [$valorAlvoStr];
                            $valorAlvo = $valoresEsperados[0] ?? null;
                            $pontuou = false;

                            switch ($operador) {
                                case '=': $pontuou = (strtolower(trim((string)$valorResposta)) === strtolower(trim((string)$valorAlvo))); break;
                                case '!=': $pontuou = (strtolower(trim((string)$valorResposta)) !== strtolower(trim((string)$valorAlvo))); break;
                                case '>=': $pontuou = ((float)$valorResposta >= (float)$valorAlvo); break;
                                case '<=': $pontuou = ((float)$valorResposta <= (float)$valorAlvo); break;
                                case 'between': $pontuou = ((float)$valorResposta >= (float)($valoresEsperados[0] ?? 0) && (float)$valorResposta <= (float)($valoresEsperados[1] ?? 0)); break;
                                case 'in': $pontuou = in_array(strtolower(trim((string)$valorResposta)), array_map('strtolower', $valoresEsperados)); break;
                            }

                            if ($pontuou) {
                                $total += $pontos;
                                $detalhes['auditoria_detalhada'][] = [
                                    'campo_avaliado' => $campo, 'resposta_dada' => $valorResposta,
                                    'pontos_ganhos' => $pontos, 'condicao' => "{$operador} " . implode(', ', $valoresEsperados)
                                ];
                            }
                        }
                    }

                    $inscricao->update([
                        'pontuacao_total' => $total,
                        'pontuacao_detalhes' => $total > 0 ? array_merge($detalhes, ['motivo_auditoria' => "Recálculo Global. Total: {$total} pts."]) : null
                    ]);
                    $atualizados++;
                }
            });
        }
        
        session()->flash('sucesso', "Recálculo finalizado! {$atualizados} inscrições atualizadas em ciclos ativos.");
    }

    public function gerarRankingGlobal()
    {
        abort_if(!auth()->user()->hasRole('dev|admin'), 403);

        // Pega APENAS os ciclos ATIVOS
        $ciclos = \App\Models\Ciclo::where('status', true)
                    ->whereNotNull('regras_pontuacao')
                    ->get();
                    
        $totalGeral = 0;

        foreach ($ciclos as $ciclo) {
            // 1. Zera todos os 4 rankings APENAS deste ciclo específico
            \App\Models\Inscricao::where('ciclo_id', $ciclo->id)
                ->update([
                    'posicao_ranking' => null, 
                    'posicao_ranking_geral' => null,
                    'posicao_ranking_unidade' => null,
                    'posicao_ranking_curso' => null,
                ]);

            // 2. Busca e ordena as inscrições (Maior Nota -> Inscrição mais antiga)
            $inscricoes = $ciclo->inscricoes()
                ->orderBy('pontuacao_total', 'desc')
                ->orderBy('created_at', 'asc')
                ->get();

            // ==========================================
            // RANKING 1: GERAL DO CICLO
            // ==========================================
            foreach ($inscricoes as $index => $inscricao) {
                $inscricao->update(['posicao_ranking_geral' => $index + 1]);
                $totalGeral++;
            }

            // ==========================================
            // RANKING 2: POR UNIDADE
            // ==========================================
            $agrupadoUnidade = $inscricoes->whereNotNull('unidade_id')->groupBy('unidade_id');
            foreach ($agrupadoUnidade as $grupo) {
                $pos = 1;
                foreach ($grupo as $inscricao) {
                    $inscricao->update(['posicao_ranking_unidade' => $pos++]);
                }
            }

            // ==========================================
            // RANKING 3: POR UNIDADE + CURSO
            // ==========================================
            $agrupadoCurso = $inscricoes->whereNotNull('unidade_id')->whereNotNull('curso_id')->groupBy(function($item) {
                return $item->unidade_id . '-' . $item->curso_id;
            });
            foreach ($agrupadoCurso as $grupo) {
                $pos = 1;
                foreach ($grupo as $inscricao) {
                    $inscricao->update(['posicao_ranking_curso' => $pos++]);
                }
            }

            // ==========================================
            // RANKING 4: TURMA (UNIDADE + CURSO + TURNO)
            // ==========================================
            $agrupadoTurma = $inscricoes->whereNotNull('unidade_id')->whereNotNull('curso_id')->whereNotNull('turno_id')->groupBy(function($item) {
                return $item->unidade_id . '-' . $item->curso_id . '-' . $item->turno_id;
            });
            foreach ($agrupadoTurma as $grupo) {
                $pos = 1;
                foreach ($grupo as $inscricao) {
                    $inscricao->update(['posicao_ranking' => $pos++]);
                }
            }
        }

        session()->flash('sucesso', "Rankings gerados! {$totalGeral} inscrições classificadas nos 4 níveis (Geral, Unidade, Curso e Turma) dentro dos ciclos ativos.");
    }

    /**
     * Helper responsável por processar a mudança de status
     * e criar o Estudante caso seja uma aprovação.
     */
    private function aplicarMudancaDeStatus($inscricoes, $statusId)
    {
        $statusNovo = \App\Models\StatusInscricao::find($statusId);
        if (!$statusNovo) return;

        $isAprovacao = strtolower($statusNovo->nome) === 'aprovado';

        foreach ($inscricoes as $inscricao) {
            // REGRA DE NEGÓCIO: Criação do Estudante
            if ($isAprovacao && !$inscricao->student_id) {
                $estudante = \App\Modules\Student\Domain\Models\Student::firstOrCreate(
                    ['email' => $inscricao->email],
                    [
                        'name' => $inscricao->nome,
                        'password' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(12)),
                        'is_active' => true,
                    ]
                );
                $inscricao->student_id = $estudante->id;
            }
            
            // Atualiza o status e salva a inscrição
            $inscricao->status_inscricao_id = $statusId;
            $inscricao->save();
        }
    }

    public function render()
    {
        // APLICAMOS O BLOQUEIO AQUI NO INÍCIO!
        // Assim, tanto a tabela quanto os cards respeitarão o isolamento de dados.
        $queryBase = $this->obterQueryFiltrada()->apenasVinculosPermitidos();
        
        // 1. Calcula os cards (Agora protegidos pelo filtro acima)
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

        // 2. APLICA A ORDENAÇÃO
        if ($this->ordenacaoCampo) {
            $queryBase->orderBy($this->ordenacaoCampo, $this->ordenacaoDirecao);
        } else {
            $queryBase->orderBy('id', 'desc');
        }

        // 3. PAGINAÇÃO SEMPRE POR ÚLTIMO (Removido o escopo daqui, pois já foi aplicado lá em cima)
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