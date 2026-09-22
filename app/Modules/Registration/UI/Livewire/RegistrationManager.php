<?php

namespace App\Modules\Registration\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\On; 
use App\Models\Inscricao;
use App\Models\Ciclo;
use App\Models\Etapa;
use Livewire\WithPagination;
use App\Traits\ComPadraoListagem;
use Illuminate\Support\Str;
use App\Helpers\BreadcrumbHelper;
use Illuminate\Support\Facades\Cache;

#[Layout('components.layouts.app')]
#[Title('Gerenciar Inscrições - Administrativo')]
class RegistrationManager extends Component
{
    use WithPagination;
    use ComPadraoListagem;

    public $filtroNome = '';
    public $filtroStatus = '';
    public $filtroCiclo = ''; 
    public $filtroUnidade = '';
    public $filtroTurno = '';
    public $filtroCurso = '';
    public $filtroEtapa = '';

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

    public bool $modalAntiSpamAberto = false;
    public array $conflitosAntiSpam = [];

    public $acaoPendenteStatusId = null;
    public array $acaoPendenteIds = [];
    public string $acaoPendenteNomeStatus = '';
    
    public function mount()
    {
        abort_if(!feature('inscricao.listar'), 403, 'O módulo de inscrições está desativado.');
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('inscricao.listar'), 403, 'Acesso restrito.');

        $this->breadcrumbs = [
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Processos Seletivos', 'url' => route('ciclos.index')],
            ['label' => 'Inscrições', 'url' => route('inscricoes.index')],
        ];

        $this->permiteGrid = true;

        $cicloAtivo = Ciclo::where('status', true)->latest()->first();
        if ($cicloAtivo) {
            $this->filtroCiclo = $cicloAtivo->id;
            $this->ciclo_id = $cicloAtivo->id; 
        }
    }

    private function getVagasConfig()
    {
        $regra = \App\Models\ConfiguracaoGeral::where('chave', 'regra_ocupacao_vaga')->value('valor') ?? 'por_status';
        $statusJson = \App\Models\ConfiguracaoGeral::where('chave', 'status_ocupacao_vaga')->value('valor');
        $statusIds = $statusJson ? json_decode($statusJson, true) : [];
        if (empty($statusIds)) {
            $statusIds = \App\Models\StatusInscricao::whereIn('nome', ['Aprovado', 'aprovado', 'Selecionado', 'selecionado'])->pluck('id')->toArray();
        }
        return ['regra' => $regra, 'status_ids' => $statusIds];
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
        $this->resetErrorBag();
    }

    public function abrirModal() {
        abort_if(!feature('inscricao.criar'), 403, 'O módulo de cadastro de inscrição está temporariamente desativado no sistema.');
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('inscricao.criar'), 403, 'Sem permissão');

        $this->resetValidation();
        $this->reset(['nome', 'cpf', 'email', 'celular']);
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

        if (\App\Models\Inscricao::where('cpf', $cpfLimpo)->exists()) {
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
            'status_inscricao_id' => 1, 
            'criado_por' => auth()->id() 
        ];

        if (auth()->user()->hasRole('dev|admin') || auth()->user()->can('inscricao.aprovar')) {
            $inscricao = \App\Models\Inscricao::create($payload);
            \App\Modules\Comunicacao\Services\AutomacaoService::disparar('inscricao.criada', $inscricao);
            $this->dispatch('sucesso', msg: 'Inscrição efetivada e e-mail de acesso enviado ao estudante!');
        } else {
            $solicitacao = \App\Models\Solicitacao::create([
                'tema' => 'cadastro_nova_inscricao',
                'solicitante_type' => \App\Models\User::class,
                'solicitante_id' => auth()->id(),
                'justificativa' => "Cadastro inserido por " . auth()->user()->name . ". Aguardando análise para liberação oficial.",
                'status' => 'pendente',
                'payload' => $payload
            ]);

            $emailSistema = \App\Models\ConfiguracaoGeral::where('chave', 'email_sistema')->value('valor') ?? 'admin@percorre.com';
            
            \App\Modules\Comunicacao\Services\AutomacaoService::disparar('inscricao.solicitacao_cadastro', $emailSistema, [
                'nome_solicitante' => auth()->user()->name,
                'justificativa' => $solicitacao->justificativa
            ]);

            $this->dispatch('sucesso', msg: 'A solicitação de cadastro foi encaminhada para aprovação da coordenação.');
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
        if (in_array($nomePropriedade, ['filtroUnidade', 'filtroTurno', 'filtroCurso', 'filtroCiclo', 'filtroNome', 'filtroStatus', 'filtroEtapa'])) {
            $this->resetPage();
            $this->desmarcarTodas(); 
            
            if ($nomePropriedade === 'filtroCiclo') {
                $this->filtroEtapa = '';
            }
        }
    }

    protected function obterQueryFiltrada()
    {
        $query = Inscricao::with(['curso', 'unidade', 'turno', 'ciclo', 'statusInscricao']);
        
        if (!empty($this->filtroNome)) {
            $query->where(function($q) {
                $q->where('inscricoes.nome', 'ilike', '%' . $this->filtroNome . '%')
                  ->orWhere('inscricoes.cpf', 'like', '%' . $this->filtroNome . '%');
            });
        }
        if (!empty($this->filtroStatus)) $query->where('inscricoes.status_inscricao_id', $this->filtroStatus);
        if (!empty($this->filtroUnidade)) $query->where('inscricoes.unidade_id', $this->filtroUnidade);
        if (!empty($this->filtroTurno)) $query->where('inscricoes.turno_id', $this->filtroTurno);
        if (!empty($this->filtroCurso)) $query->where('inscricoes.curso_id', $this->filtroCurso);
        if (!empty($this->filtroCiclo)) $query->where('inscricoes.ciclo_id', $this->filtroCiclo);
        if (!empty($this->filtroEtapa)) {
            if ($this->filtroEtapa === 'Finalizado') {
                $query->where('inscricoes.etapa_atual', 99);
            } else {
                $query->where('inscricoes.etapa_atual', $this->filtroEtapa);
            }
        }

        return $query; 
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

    private function validarVagasDisponiveisParaAprovacao($inscricoes, $statusId)
    {
        $config = $this->getVagasConfig();

        if (!in_array((string)$statusId, $config['status_ids']) && !in_array((int)$statusId, $config['status_ids'])) {
            return $inscricoes; 
        }

        $agrupadas = $inscricoes->groupBy(function($insc) {
            return "{$insc->unidade_id}-{$insc->curso_id}-{$insc->turno_id}";
        });

        $inscricoesAprovadas = collect();

        foreach ($agrupadas as $chave => $grupoInscricoes) {
            $partes = explode('-', $chave);
            if (count($partes) !== 3 || empty($partes[0]) || empty($partes[1]) || empty($partes[2])) {
                $this->dispatch('erro', msg: 'Algumas inscrições sem vínculos acadêmicos foram ignoradas na aprovação.');
                continue;
            }

            $unidade_id = $partes[0];
            $curso_id = $partes[1];
            $turno_id = $partes[2];

            $oferta = \App\Models\OfertaVaga::where('ciclo_id', $this->filtroCiclo ?? $grupoInscricoes->first()->ciclo_id)
                ->where('unidade_id', $unidade_id)
                ->where('curso_id', $curso_id)
                ->where('turno_id', $turno_id)
                ->first();

            if (!$oferta) {
                $this->dispatch('erro', msg: "Não há oferta de vagas configurada para uma das combinações selecionadas. Ignorado.");
                continue;
            }

            $queryOcupadas = \App\Models\Inscricao::where('ciclo_id', $this->filtroCiclo ?? $grupoInscricoes->first()->ciclo_id)
                ->where('unidade_id', $unidade_id)
                ->where('curso_id', $curso_id)
                ->where('turno_id', $turno_id)
                ->whereNotIn('id', $grupoInscricoes->pluck('id')->toArray());

            if ($config['regra'] === 'por_matricula') {
                $queryOcupadas->where(function($q) use ($config) {
                    $q->whereIn('student_id', \App\Modules\Student\Domain\Models\Student::where('matriculado', true)->select('id'))
                      ->orWhereIn('status_inscricao_id', $config['status_ids']);
                });
            } else {
                $queryOcupadas->whereIn('status_inscricao_id', $config['status_ids']);
            }

            $ocupadas = $queryOcupadas->count();

            $vagasRestantes = $oferta->vagas - $ocupadas;
            $tentandoAprovar = $grupoInscricoes->count();

            if ($vagasRestantes <= 0) {
                $cursoNome = $grupoInscricoes->first()->curso->nome ?? 'Curso';
                $this->dispatch('erro', msg: "Vagas esgotadas para {$cursoNome}. Nenhuma nova inscrição foi aprovada nesta turma.");
                continue;
            }

            if ($tentandoAprovar > $vagasRestantes) {
                $cursoNome = $grupoInscricoes->first()->curso->nome ?? 'Curso';
                $this->dispatch('erro', msg: "Atenção: Restavam apenas {$vagasRestantes} vagas para {$cursoNome}. Aprovamos apenas essa quantidade respeitando a ordem do ranking!");
                $inscricoesAprovadas = $inscricoesAprovadas->merge($grupoInscricoes->take($vagasRestantes));
            } else {
                $inscricoesAprovadas = $inscricoesAprovadas->merge($grupoInscricoes);
            }
        }

        return $inscricoesAprovadas;
    }

    #[On('quick-change-status')]
    public function alterarStatusQuickView($id, $statusId)
    {
        abort_if(!feature('inscricao.editar'), 403);
        $inscricao = Inscricao::with('curso')->find($id);
        if ($inscricao && $inscricao->status_inscricao_id == $statusId) {
            $this->dispatch('erro', msg: 'O candidato já está neste status!');
            return;
        }

        $inscricoesProcessar = $this->validarVagasDisponiveisParaAprovacao(collect([$inscricao]), $statusId);
        if ($inscricoesProcessar->isEmpty()) return;

        $this->verificarAntiSpam($inscricoesProcessar, $statusId, false);
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
        $this->selecionadas = $query->take((int) $quantidade)->pluck('id')->map(fn($id) => (string) $id)->toArray();
        $this->dispatch('sucesso', msg: count($this->selecionadas) . ' inscrições selecionadas e a tabela foi reordenada.');
    }

    public function abrirModalSelecaoAvancada() { $this->modalSelecaoAvancadaAberto = true; }

    public function executarSelecaoAvancada()
    {
        $this->validate(['selecaoQtd' => 'required|integer|min:1']);
        
        if (empty($this->filtroCiclo)) {
            $this->dispatch('erro', msg: 'Por favor, selecione um Ciclo específico no filtro superior antes de utilizar a seleção avançada.');
            return;
        }

        $idsSelecionados = [];

        if ($this->selecaoPreencherVagas) {
            $queryOfertas = \App\Models\OfertaVaga::query();
            $queryOfertas->where('ciclo_id', $this->filtroCiclo);
            $config = $this->getVagasConfig();

            foreach ($queryOfertas->get() as $oferta) {
                if ($oferta->vagas <= 0) continue;
                
                $queryOcupadas = \App\Models\Inscricao::where('ciclo_id', $oferta->ciclo_id)
                    ->where('unidade_id', $oferta->unidade_id)
                    ->where('curso_id', $oferta->curso_id)
                    ->where('turno_id', $oferta->turno_id);

                if ($config['regra'] === 'por_matricula') {
                    $queryOcupadas->where(function($q) use ($config) {
                        $q->whereIn('student_id', \App\Modules\Student\Domain\Models\Student::where('matriculado', true)->select('id'))
                          ->orWhereIn('status_inscricao_id', $config['status_ids']);
                    });
                } else {
                    $queryOcupadas->whereIn('status_inscricao_id', $config['status_ids']);
                }

                $vagasOcupadas = $queryOcupadas->count();
                $vagasRestantes = $oferta->vagas - $vagasOcupadas;
                if ($vagasRestantes <= 0) continue;

                $queryInsc = $this->obterQueryFiltrada()
                    ->where('ciclo_id', $oferta->ciclo_id)->where('unidade_id', $oferta->unidade_id)
                    ->where('curso_id', $oferta->curso_id)->where('turno_id', $oferta->turno_id)
                    ->whereNotIn('id', $idsSelecionados);

                if ((clone $queryInsc)->whereNotNull('posicao_ranking')->exists()) {
                    $queryInsc->orderByRaw('posicao_ranking ASC NULLS LAST');
                } elseif ((clone $queryInsc)->where('pontuacao_total', '>', 0)->exists()) {
                    $queryInsc->orderBy('pontuacao_total', 'desc')->orderBy('created_at', 'asc');
                } else {
                    $queryInsc->orderBy('id', 'asc');
                }

                $ids = $queryInsc->limit($vagasRestantes)->pluck('id')->toArray();
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
        if (!$this->modalLoteAberto || empty($this->selecionadas)) return collect();
        return Inscricao::with(['curso', 'unidade', 'statusInscricao'])->whereIn('id', $this->selecionadas)->get();
    }

    public function desmarcarIndividual($id)
    {
        if (($key = array_search((string)$id, $this->selecionadas)) !== false || ($key = array_search((int)$id, $this->selecionadas)) !== false) {
            unset($this->selecionadas[$key]);
            $this->selecionadas = array_values($this->selecionadas);
        }
        if (count($this->selecionadas) === 0) $this->modalLoteAberto = false;
    }

    public function alterarStatusLoteRapido($statusId)
    {
        abort_if(!feature('inscricao.editar'), 403);
        if (count($this->selecionadas) === 0) return;
        
        $inscricoesValidas = Inscricao::with('curso')->whereIn('id', $this->selecionadas)->where('status_inscricao_id', '!=', $statusId)->get();
        if ($inscricoesValidas->isEmpty()) {
            $this->dispatch('erro', msg: 'Todas as inscrições selecionadas já estão neste status!');
            return;
        }

        $inscricoesValidas = $inscricoesValidas->sortBy(function($model) {
            return array_search((string)$model->id, $this->selecionadas);
        })->values();

        $inscricoesProcessar = $this->validarVagasDisponiveisParaAprovacao($inscricoesValidas, $statusId);
        if ($inscricoesProcessar->isEmpty()) return;

        $this->verificarAntiSpam($inscricoesProcessar, $statusId, true);
    }

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
            
            $this->acaoPendenteStatusId = $statusId;
            $this->acaoPendenteIds = $idsValidos;
            $this->acaoPendenteNomeStatus = $statusNovo->nome;
            
            $this->modalAntiSpamAberto = true;
            $this->modalLoteAberto = false; 
            return; 
        }

        $this->executarMudancaStatusFinal($idsValidos, $statusId);
    }

    public function removerConflitoAntiSpam($idConflito)
    {
        $this->conflitosAntiSpam = array_values(array_filter($this->conflitosAntiSpam, fn($c) => $c['id'] != $idConflito));
        $this->acaoPendenteIds = array_values(array_diff($this->acaoPendenteIds, [$idConflito]));

        if (empty($this->conflitosAntiSpam)) {
            if (empty($this->acaoPendenteIds)) {
                $this->cancelarAntiSpam();
                $this->dispatch('erro', msg: 'Nenhuma inscrição restou para ser processada.');
                return;
            }
            $this->prosseguirComReenvioAntiSpam();
        }
    }

    public function prosseguirComReenvioAntiSpam()
    {
        $this->executarMudancaStatusFinal($this->acaoPendenteIds, $this->acaoPendenteStatusId);
        $this->cancelarAntiSpam();
    }

    public function cancelarAntiSpam()
    {
        $this->modalAntiSpamAberto = false;
        $this->conflitosAntiSpam = [];
        $this->acaoPendenteStatusId = null;
        $this->acaoPendenteIds = [];
        $this->acaoPendenteNomeStatus = '';
    }

    private function executarMudancaStatusFinal($ids, $statusId)
    {
        $statusNovo = \App\Models\StatusInscricao::find($statusId);
        $qtd = count($ids);
        
        if ($qtd <= 5) {
            $inscricoes = Inscricao::whereIn('id', $ids)->get();
            $eventoGatilho = 'inscricao.status.' . \Illuminate\Support\Str::slug($statusNovo->nome, '_');

            foreach ($inscricoes as $insc) {
                $insc->status_inscricao_id = $statusId;
                $insc->save();
                \App\Modules\Comunicacao\Services\AutomacaoService::disparar($eventoGatilho, $insc);
            }

            $this->desmarcarTodas();
            $this->dispatch('sucesso', msg: 'Status atualizado com sucesso!');
            
            if ($qtd === 1) {
                $this->showQuickView($ids[0]);
            }
        } else {
            $tracking = \App\Models\Importacao::create([
                'user_id' => auth()->id(), 'tipo' => 'inscricoes', 'operacao' => 'atualizacao_lote', 'formato' => 'system',
                'arquivo_nome' => "Alteração de Status: {$qtd} registros para '{$statusNovo->nome}'", 'status' => 'na_fila', 'total_linhas' => $qtd, 'linhas_processadas' => 0,
            ]);

            dispatch(new \App\Jobs\ProcessarStatusEmLoteJob($tracking->id, $ids, $statusId))->afterResponse();
            $this->desmarcarTodas();
            
            $this->dispatch('sucesso', msg: 'Ação autorizada e enviada para a Nuvem!');
        }
    }

    public function salvarStatusEmLote()
    {
        $this->validate(['novoStatusId' => 'required', 'selecionadas' => 'required|array|min:1']);
        $this->alterarStatusLoteRapido($this->novoStatusId);
    }

    public function solicitarExportacao($formato = 'csv')
    {
        if (!is_string($formato)) {
            $formato = 'csv';
        }

        $filtrosAtuais = [
            'nome' => $this->filtroNome,
            'status' => $this->filtroStatus,
            'ciclo' => $this->filtroCiclo,
            'unidade' => $this->filtroUnidade,
            'turno' => $this->filtroTurno,
            'curso' => $this->filtroCurso,
            'etapa' => $this->filtroEtapa,
        ];

        $queryCount = $this->obterQueryFiltrada()->count();

        if ($queryCount === 0) {
            $this->dispatch('erro', msg: 'Não há registros com os filtros atuais para exportar.');
            return;
        }

        $tracking = \App\Models\Importacao::create([
            'user_id' => auth()->id(),
            'tipo' => 'inscricoes',
            'operacao' => 'exportacao',
            'formato' => strtolower($formato),
            'arquivo_nome' => 'Exportação de Inscrições (' . strtoupper($formato) . ')',
            'status' => 'na_fila',
            'total_linhas' => $queryCount,
            'linhas_processadas' => 0,
        ]);

        dispatch(new \App\Jobs\ExportarInscricoesFiltradasJob($tracking->id, $filtrosAtuais))->afterResponse();

        $this->dispatch('sucesso', msg: 'Exportação enviada para o plano de fundo! Acompanhe a geração do arquivo no Gerenciador (I/O).');
    }

    public function recalcularScoresGlobais()
    {
        abort_if(!feature('inscricao.editar'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('inscricao.editar'), 403);
        
        if (empty($this->filtroCiclo)) {
            $this->dispatch('erro', msg: 'Por favor, selecione um Ciclo específico no filtro superior para recalcular a pontuação.');
            return;
        }

        $ciclo = Ciclo::find($this->filtroCiclo);
        $nomeCiclo = $ciclo ? $ciclo->nome : 'Ciclo Filtrado';

        $trackingScore = \App\Models\Importacao::create([
            'user_id' => auth()->id(), 'tipo' => 'inscricoes', 'operacao' => 'recalculo', 'formato' => 'system',
            'arquivo_nome' => '1/2: Recálculo de Pontuação (' . $nomeCiclo . ')', 'status' => 'na_fila', 'total_linhas' => 0, 'linhas_processadas' => 0,
        ]);

        $trackingRank = \App\Models\Importacao::create([
            'user_id' => auth()->id(), 'tipo' => 'inscricoes', 'operacao' => 'ranking', 'formato' => 'system',
            'arquivo_nome' => '2/2: Geração de Ranking (' . $nomeCiclo . ')', 'status' => 'na_fila', 'total_linhas' => 0, 'linhas_processadas' => 0,
        ]);

        \Illuminate\Support\Facades\Bus::chain([
            new \App\Jobs\RecalcularPontuacoesGlobaisJob($trackingScore->id, $this->filtroCiclo),
            new \App\Jobs\GerarRankingGlobalJob($trackingRank->id, $this->filtroCiclo)
        ])->dispatch();
        
        $this->dispatch('sucesso', msg: "Processamento iniciado para o ciclo selecionado! Acompanhe no Gerenciador de Integrações.");
    }

    public function gerarRankingGlobal()
    {
        abort_if(!feature('inscricao.editar'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('inscricao.editar'), 403);

        if (empty($this->filtroCiclo)) {
            $this->dispatch('erro', msg: 'Por favor, selecione um Ciclo específico no filtro superior para gerar o ranking.');
            return;
        }

        $ciclo = Ciclo::find($this->filtroCiclo);
        $nomeCiclo = $ciclo ? $ciclo->nome : 'Ciclo Filtrado';

        $tracking = \App\Models\Importacao::create([
            'user_id' => auth()->id(), 'tipo' => 'inscricoes', 'operacao' => 'ranking', 'formato' => 'system',
            'arquivo_nome' => 'Geração de Ranking: ' . $nomeCiclo, 'status' => 'na_fila', 'total_linhas' => 0, 'linhas_processadas' => 0,
        ]);

        dispatch(new \App\Jobs\GerarRankingGlobalJob($tracking->id, $this->filtroCiclo))->afterResponse();
        
        $this->dispatch('sucesso', msg: "O motor de Ranking foi iniciado para o ciclo selecionado. Acompanhe no Gerenciador de Integrações.");
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
                'confirm' => 'Gerar a posição de ranking cruzado para as inscrições do Ciclo selecionado no filtro? O motor processará em segundo plano.'
            ],
            [
                'label' => 'Recalcular Pontuação',
                'icon' => 'ph ph-calculator',
                'wire_click' => 'recalcularScoresGlobais',
                'always_show_label' => true,
                'bg_color' => 'bg-orange-500 hover:bg-orange-600',
                'icon_color' => 'text-white',
                'confirm' => 'Processar as pontuações e regras Multiplicadoras das inscrições do Ciclo selecionado? Essa ação rodará na nuvem.'
            ]
        ];
    }

    public function limparFiltros()
    {
        $this->reset(['filtroNome', 'filtroStatus', 'filtroCiclo', 'filtroUnidade', 'filtroTurno', 'filtroCurso', 'filtroEtapa']);
        $this->resetPage();
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
            ['key' => 'pontuacao_total', 'label' => 'Pontuação', 'sortable' => true, 'class' => 'text-center'],
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
        
        $statusCounts = (clone $queryBase)
            ->join('status_inscricoes', 'inscricoes.status_inscricao_id', '=', 'status_inscricoes.id')
            ->selectRaw('status_inscricoes.nome as status_nome, count(inscricoes.id) as total')
            ->groupBy('status_inscricoes.nome')
            ->pluck('total', 'status_nome')
            ->toArray();

        $totalGeral = (clone $queryBase)->count();
        $totalAprovados = $statusCounts['Aprovado'] ?? 0;
        $totalReprovados = $statusCounts['Reprovado'] ?? 0;
        $totalPendentes = $totalGeral - ($totalAprovados + $totalReprovados);

        $metricas = [
            ['label' => 'Total', 'value' => $totalGeral, 'color_text' => 'text-blue-600 dark:text-blue-400', 'color_bg' => 'bg-blue-100 dark:bg-blue-900/30'],
            ['label' => 'Aprovados', 'value' => $totalAprovados, 'color_text' => 'text-green-600 dark:text-green-400', 'color_bg' => 'bg-green-100 dark:bg-green-900/30'],
            ['label' => 'Reprovados', 'value' => $totalReprovados, 'color_text' => 'text-red-600 dark:text-red-400', 'color_bg' => 'bg-red-100 dark:bg-red-900/30'],
            ['label' => 'Pendentes', 'value' => $totalPendentes, 'color_text' => 'text-yellow-600 dark:text-yellow-400', 'color_bg' => 'bg-yellow-100 dark:bg-yellow-900/30'],
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

        $etapasDb = [];
        if (!empty($this->filtroCiclo)) {
            $etapasDb = Etapa::where('ciclo_id', $this->filtroCiclo)->orderBy('numero', 'asc')->pluck('nome', 'numero')->toArray();
        }

        $dropdowns = Cache::remember('filtros_registration_arr', 3600, function() {
            return [
                'status' => \App\Models\StatusInscricao::orderBy('nome')->pluck('nome', 'id')->toArray(),
                'ciclos' => \App\Models\Ciclo::orderBy('id', 'desc')->pluck('nome', 'id')->toArray(),
                'unidades' => \App\Modules\Unidade\Domain\Models\Unidade::whereIn('status', ['Ativa', '1', true])->pluck('nome', 'id')->toArray(),
                'turnos' => \App\Modules\Turno\Domain\Models\Turno::orderBy('nome')->pluck('nome', 'id')->toArray(),
                'cursos' => \App\Models\Curso::whereIn('status', ['Ativo', '1', true])->pluck('nome', 'id')->toArray(),
            ];
        });

        return view('livewire.registration.registration-manager', [
            'registros' => $inscricoes,
            'metricas' => $metricas,
            'etapasDb' => $etapasDb,
            'statusInscricoesDb' => $dropdowns['status'],
            'ciclosDb' => $dropdowns['ciclos'],
            'unidadesDb' => $dropdowns['unidades'],
            'turnosDb' => $dropdowns['turnos'],
            'cursosDb' => $dropdowns['cursos'],
        ]);
    }
}