<?php

namespace App\Modules\Period\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\On;
use App\Models\Ciclo;
use App\Models\Curso;
use App\Models\Inscricao;
use App\Models\StatusInscricao;
use Livewire\WithPagination;
use App\Traits\ComPadraoListagem;
use App\Traits\WithToggleStatus;
use App\Helpers\BreadcrumbHelper;

#[Layout('components.layouts.app')]
#[Title('Detalhes do Ciclo')]
class PeriodDetails extends Component
{
    use WithPagination, ComPadraoListagem, WithToggleStatus {
        WithToggleStatus::toggleStatus as traitToggleStatus;
    }

    public Ciclo $ciclo;
    public string $modelClass = \App\Models\Ciclo::class; 
    public $cursoSelecionado = '';

    // Filtros
    public $filtroNome = '';
    public $filtroStatus = '';
    public $filtroUnidade = '';
    public $filtroTurno = '';
    public $filtroCurso = '';

    // Ações em Lote
    public array $selecionadas = [];
    public bool $modalLoteAberto = false;
    public $novoStatusId = '';

    public array $breadcrumbs = [];

    public bool $unicoAtivo = true;

    public function mount($id)
    {
        abort_if(!auth()->user()->hasRole('dev|admin'), 403);
        $this->ciclo = Ciclo::with('cursos')->findOrFail($id);
        $this->breadcrumbs = BreadcrumbHelper::generate();
        $this->permiteGrid = true;
    }

    public function toggleStatus($id)
    {
        // 1. Executa a lógica original da Trait (Desativar outros e salvar no banco)
        $this->traitToggleStatus($id);
        
        // 2. Força a variável da tela a buscar os dados atualizados direto do banco
        $this->ciclo->refresh(); 
    }

    public function updating($nomePropriedade)
    {
        if (in_array($nomePropriedade, ['filtroUnidade', 'filtroTurno', 'filtroCurso', 'filtroNome', 'filtroStatus'])) {
            $this->resetPage();
            $this->desmarcarTodas();
        }
    }

    // ==========================================
    // GESTÃO DO CICLO (CURSOS OFERTADOS)
    // ==========================================
    public function adicionarCurso()
    {
        if (empty($this->cursoSelecionado)) {
            $this->dispatch('erro', msg: 'Selecione um curso na lista primeiro!');
            return;
        }
        
        // Compara especificamente pelo ID para evitar erros de tipagem (string vs int)
        if (!$this->ciclo->cursos->contains('id', $this->cursoSelecionado)) {
            $this->ciclo->cursos()->attach($this->cursoSelecionado);
            $this->ciclo->load('cursos');
            $this->dispatch('sucesso', msg: 'Curso vinculado ao ciclo com sucesso!');
        } else {
            $this->dispatch('erro', msg: 'Este curso já está ofertado neste ciclo.');
        }
        
        $this->cursoSelecionado = '';
    }

    public function limparFiltros()
    {
        $this->reset(['filtroNome', 'filtroStatus', 'filtroUnidade', 'filtroTurno', 'filtroCurso']);
        $this->resetPage();
        $this->desmarcarTodas();
    }

    public function removerCurso($cursoId)
    {
        $this->ciclo->cursos()->detach($cursoId);
        $this->ciclo->load('cursos');
        $this->dispatch('sucesso', msg: 'Curso removido do ciclo.');
    }

    // ==========================================
    // LISTAGEM E FILTROS DE INSCRIÇÕES
    // ==========================================
    protected function obterQueryFiltrada()
    {
        // Trava a busca APENAS neste ciclo
        $query = Inscricao::with(['curso', 'unidade', 'turno', 'statusInscricao'])
                          ->where('ciclo_id', $this->ciclo->id);
        
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

        return $query; 
    }

    // ==========================================
    // QUICK VIEW E MUDANÇA DE STATUS (C/ ESTUDANTE)
    // ==========================================
    private function aplicarMudancaDeStatus($inscricoes, $statusId)
    {
        $statusNovo = StatusInscricao::find($statusId);
        if (!$statusNovo) return;

        $isAprovacao = strtolower($statusNovo->nome) === 'aprovado';

        foreach ($inscricoes as $inscricao) {
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
            $inscricao->status_inscricao_id = $statusId;
            $inscricao->save();
        }
    }

    #[On('quick-change-status')]
    public function alterarStatusQuickView($id, $status)
    {
        $inscricao = Inscricao::find($id);
        if ($inscricao) {
            $this->aplicarMudancaDeStatus([$inscricao], $status);
            $this->dispatch('sucesso', msg: 'Status atualizado com sucesso!');
            $this->showQuickView($id);
        }
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
            $botoesAcao .= '<button @click="$dispatch(\'quick-change-status\', { id: '.$id.', status: '.$st->id.' })" class="px-3 py-1.5 text-[11px] uppercase font-bold border rounded shadow-sm transition-colors '.$corClass.'">'.$st->nome.'</button>';
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
            $detalhesDinamicos = '<span class="text-gray-500 text-sm italic">Nenhum dado complementar.</span>';
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

    // ==========================================
    // AÇÕES EM LOTE
    // ==========================================
    public function selecionarQuantidade($quantidade)
    {
        $this->selecionadas = $this->obterQueryFiltrada()->limit($quantidade)->pluck('id')->map(fn($id) => (string) $id)->toArray();
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
        $inscricoes = Inscricao::whereIn('id', $this->selecionadas)->get();
        $this->aplicarMudancaDeStatus($inscricoes, $this->novoStatusId);
        $this->modalLoteAberto = false;
        $this->desmarcarTodas(); 
        $this->dispatch('sucesso', msg: 'Status alterado em lote com sucesso!');
    }
    public function alterarStatusLoteRapido($statusId)
    {
        if (count($this->selecionadas) === 0) return;
        $inscricoes = Inscricao::whereIn('id', $this->selecionadas)->get();
        $this->aplicarMudancaDeStatus($inscricoes, $statusId);
        $this->desmarcarTodas();
        $this->dispatch('sucesso', msg: 'Status alterado rapidamente com sucesso!');
    }

    // ==========================================
    // RECÁLCULO E RANKING ESPECÍFICO DESTE CICLO
    // ==========================================
    public function recalcularPontuacoes()
    {
        abort_if(!auth()->user()->hasRole('dev|admin'), 403);
        $regras = is_string($this->ciclo->regras_pontuacao) ? json_decode($this->ciclo->regras_pontuacao, true) : $this->ciclo->regras_pontuacao;
        
        if (empty($regras)) {
            $this->dispatch('erro', msg: 'Não há regras de pontuação configuradas neste ciclo.');
            return;
        }

        $atualizados = 0;
        $this->ciclo->inscricoes()->chunk(100, function ($inscricoes) use ($regras, &$atualizados) {
            foreach ($inscricoes as $inscricao) {
                $total = 0;
                $detalhes = ['auditoria_detalhada' => []];
                $respostas = is_string($inscricao->dados_dinamicos) ? json_decode($inscricao->dados_dinamicos, true) : ($inscricao->dados_dinamicos ?? []);

                foreach ($regras as $regra) {
                    $campo = trim($regra['campo'] ?? '');
                    $operador = trim($regra['operador'] ?? '=');
                    $pontos = (int) ($regra['pontos'] ?? 0);
                    $valorResposta = null;

                    if ($campo === 'idade' && $inscricao->data_nascimento) $valorResposta = \Carbon\Carbon::parse($inscricao->data_nascimento)->age;
                    elseif (in_array($campo, ['estado', 'cidade', 'curso_id', 'turno_id', 'possui_deficiencia'])) $valorResposta = $inscricao->$campo;
                    elseif (isset($respostas[$campo])) $valorResposta = $respostas[$campo];

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
                                'campo_avaliado' => $campo, 'resposta_dada' => $valorResposta, 'pontos_ganhos' => $pontos, 'condicao' => "{$operador} " . implode(', ', $valoresEsperados)
                            ];
                        }
                    }
                }
                $inscricao->update([
                    'pontuacao_total' => $total,
                    'pontuacao_detalhes' => $total > 0 ? array_merge($detalhes, ['motivo_auditoria' => "Recálculo do Ciclo. Total: {$total} pts."]) : null
                ]);
                $atualizados++;
            }
        });
        $this->dispatch('sucesso', msg: "{$atualizados} inscrições recalculadas com sucesso!");
    }

    public function gerarRanking()
    {
        abort_if(!auth()->user()->hasRole('dev|admin'), 403);

        Inscricao::where('ciclo_id', $this->ciclo->id)
            ->update([
                'posicao_ranking' => null, 
                'posicao_ranking_geral' => null,
                'posicao_ranking_unidade' => null,
                'posicao_ranking_curso' => null,
            ]);

        $inscricoes = $this->ciclo->inscricoes()->orderBy('pontuacao_total', 'desc')->orderBy('created_at', 'asc')->get();
        $totalGeral = 0;

        foreach ($inscricoes as $index => $inscricao) { $inscricao->update(['posicao_ranking_geral' => $index + 1]); $totalGeral++; }
        
        $agrupadoUnidade = $inscricoes->whereNotNull('unidade_id')->groupBy('unidade_id');
        foreach ($agrupadoUnidade as $grupo) { $pos = 1; foreach ($grupo as $inscricao) { $inscricao->update(['posicao_ranking_unidade' => $pos++]); } }

        $agrupadoCurso = $inscricoes->whereNotNull('unidade_id')->whereNotNull('curso_id')->groupBy(function($item) { return $item->unidade_id . '-' . $item->curso_id; });
        foreach ($agrupadoCurso as $grupo) { $pos = 1; foreach ($grupo as $inscricao) { $inscricao->update(['posicao_ranking_curso' => $pos++]); } }

        $agrupadoTurma = $inscricoes->whereNotNull('unidade_id')->whereNotNull('curso_id')->whereNotNull('turno_id')->groupBy(function($item) { return $item->unidade_id . '-' . $item->curso_id . '-' . $item->turno_id; });
        foreach ($agrupadoTurma as $grupo) { $pos = 1; foreach ($grupo as $inscricao) { $inscricao->update(['posicao_ranking' => $pos++]); } }

        $this->dispatch('sucesso', msg: "Rankings gerados! {$totalGeral} classificados nos 4 níveis.");
    }

    public function getHeadersProperty()
    {
        return [
            ['key' => 'checkbox', 'label' => '', 'sortable' => false, 'class' => 'w-10 text-center'],
            ['key' => 'id', 'label' => 'ID', 'sortable' => true],
            ['key' => 'nome', 'label' => 'Candidato', 'sortable' => true],
            ['key' => 'curso_id', 'label' => 'Curso', 'sortable' => false],
            ['key' => 'etapa_atual', 'label' => 'Etapa', 'sortable' => true],
            ['key' => 'pontuacao_total', 'label' => 'Score / Ranking', 'sortable' => true, 'class' => 'text-center'],
            ['key' => 'status', 'label' => 'Status', 'sortable' => false],
            ['key' => 'acoes', 'label' => 'Ações', 'sortable' => false, 'class' => 'text-right'],
        ];
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
            $queryBase->orderBy($this->ordenacaoCampo, $this->ordenacaoDirecao);
        } else {
            $queryBase->orderBy('id', 'desc');
        }

        $inscricoes = $queryBase->paginate($this->porPagina);

        return view('livewire.period.period-details', [
            'registros' => $inscricoes,
            'metricas' => $metricas,
            'statusInscricoesDb' => StatusInscricao::orderBy('nome')->get(),
            'unidadesDb' => \App\Modules\Unidade\Domain\Models\Unidade::whereIn('status', ['Ativa', '1', true])->get(),
            'turnosDb' => \App\Modules\Turno\Domain\Models\Turno::orderBy('nome')->get(),
            'cursosDisponiveis' => Curso::whereIn('status', ['Ativo', 'ativo', '1', 1, true])->orderBy('nome')->get()
        ]);
    }
}