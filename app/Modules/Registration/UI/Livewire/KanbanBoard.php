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

    public $filtroBusca = '';
    public $filtroCurso = '';
    public $filtroUnidade = '';
    public $filtroDataFim = '';
    public $ordenacao = 'recentes'; 

    public bool $modalAntiSpamAberto = false;
    public array $conflitosAntiSpam = [];
    
    // VARIÁVEIS SEGURAS (Igual ao Manager)
    public $acaoPendenteStatusId = null;
    public array $acaoPendenteIds = [];
    public string $acaoPendenteNomeStatus = '';

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

    #[On('atualizar-status-crm')] // Para suportar disparos via eventos, se necessário no futuro
    public function atualizarStatus($id, $statusId)
    {
        abort_if(!feature('inscricao.editar'), 403);

        $inscricao = Inscricao::with('curso')->find($id);
        
        // Verifica se a inscrição existe e se realmente mudou de status
        if (!$inscricao || $inscricao->status_inscricao_id == $statusId) {
            return;
        }

        // Valida as vagas e regras de negócio antes de aprovar
        $inscricoesProcessar = $this->validarVagasDisponiveisParaAprovacao(collect([$inscricao]), $statusId);
        
        if ($inscricoesProcessar->isEmpty()) return;

        // Passa pelo funil de Anti-Spam antes de finalizar a mudança
        $this->verificarAntiSpam($inscricoesProcessar, $statusId, false);
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

    private function validarVagasDisponiveisParaAprovacao($inscricoes, $statusId)
    {
        $config = $this->getVagasConfig();

        if (!in_array((string)$statusId, $config['status_ids']) && !in_array((int)$statusId, $config['status_ids'])) {
            return $inscricoes; 
        }

        $agrupadas = $inscricoes->groupBy(function($insc) { return "{$insc->unidade_id}-{$insc->curso_id}-{$insc->turno_id}"; });

        $inscricoesAprovadas = collect();

        foreach ($agrupadas as $chave => $grupoInscricoes) {
            $partes = explode('-', $chave);
            if (count($partes) !== 3 || empty($partes[0]) || empty($partes[1]) || empty($partes[2])) {
                $this->dispatch('erro', msg: 'Inscrições sem vínculos não puderam ser processadas para preenchimento de vaga.');
                continue;
            }

            $oferta = \App\Models\OfertaVaga::where('ciclo_id', $this->cicloId ?? $grupoInscricoes->first()->ciclo_id)->where('unidade_id', $partes[0])->where('curso_id', $partes[1])->where('turno_id', $partes[2])->first();

            if (!$oferta) continue;

            $queryOcupadas = \App\Models\Inscricao::where('ciclo_id', $this->cicloId ?? $grupoInscricoes->first()->ciclo_id)
                ->where('unidade_id', $partes[0])
                ->where('curso_id', $partes[1])
                ->where('turno_id', $partes[2])
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
                $this->dispatch('erro', msg: "Vagas esgotadas para {$cursoNome}. Movimentação impedida.");
                continue;
            }

            if ($tentandoAprovar > $vagasRestantes) {
                $cursoNome = $grupoInscricoes->first()->curso->nome ?? 'Curso';
                $this->dispatch('erro', msg: "Atenção: Aprovamos apenas as {$vagasRestantes} vagas que restavam para {$cursoNome}.");
                $inscricoesAprovadas = $inscricoesAprovadas->merge($grupoInscricoes->take($vagasRestantes));
            } else {
                $inscricoesAprovadas = $inscricoesAprovadas->merge($grupoInscricoes);
            }
        }
        return $inscricoesAprovadas;
    }

    #[On('quick-change-status-crm')]
    public function quickChangeStatus($id, $status)
    {
        $inscricao = Inscricao::with('curso')->find($id);
        if ($inscricao && $inscricao->status_inscricao_id == $status) {
            $this->dispatch('erro', msg: 'O candidato já está nesta etapa!');
            return;
        }

        $inscricoesProcessar = $this->validarVagasDisponiveisParaAprovacao(collect([$inscricao]), $status);
        if ($inscricoesProcessar->isEmpty()) return;

        $this->verificarAntiSpam($inscricoesProcessar, $status, false);
    }

    public function moverLote()
    {
        abort_if(!feature('inscricao.editar'), 403);
        $this->validate(['selecionados' => 'required|array|min:1', 'statusDestinoLote' => 'required|exists:status_inscricoes,id']);

        $inscricoesValidas = Inscricao::with('curso')->whereIn('id', $this->selecionados)->where('status_inscricao_id', '!=', $this->statusDestinoLote)->get();
        if ($inscricoesValidas->isEmpty()) {
            $this->dispatch('erro', msg: 'Todas as inscrições já estão na coluna de destino!');
            return;
        }

        $inscricoesValidas = $inscricoesValidas->sortBy(function($model) {
            return array_search((string)$model->id, $this->selecionados);
        })->values();

        $inscricoesProcessar = $this->validarVagasDisponiveisParaAprovacao($inscricoesValidas, $this->statusDestinoLote);
        if ($inscricoesProcessar->isEmpty()) return;

        $this->verificarAntiSpam($inscricoesProcessar, $this->statusDestinoLote, true);
    }

    #[On('open-regras-crm')]
    public function abrirRegras(int $id)
    {
        $inscricao = Inscricao::with('ciclo')->find($id);
        if (!$inscricao) return;

        $regrasRaw = is_string($inscricao->ciclo->regras_pontuacao)
            ? json_decode($inscricao->ciclo->regras_pontuacao, true)
            : ($inscricao->ciclo->regras_pontuacao ?? []);

        $detalhes = is_string($inscricao->pontuacao_detalhes)
            ? json_decode($inscricao->pontuacao_detalhes, true)
            : ($inscricao->pontuacao_detalhes ?? []);

        $auditoria = $detalhes['auditoria_detalhada'] ?? [];

        $agrupadas = collect($regrasRaw)->groupBy(function($r) {
            return $r['campo'] ?? 'Regra Global';
        });

        $html = '<div class="space-y-6">';
        foreach ($agrupadas as $campo => $regras) {
            $nomeCampoFormatado = str_replace('_', ' ', strtoupper($campo));
            $html .= "<div class='bg-gray-50 dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 shadow-sm'>";
            $html .= "<h4 class='text-xs font-bold text-gray-500 mb-3 tracking-wider flex items-center gap-2'><i class='ph-fill ph-check-square-offset text-purpura-500'></i> {$nomeCampoFormatado}</h4>";
            $html .= "<div class='space-y-2'>";

            foreach ($regras as $regra) {
                $tipo = $regra['tipo_regra'] ?? 'padrao';
                $pontos = $regra['pontos'] ?? 0;
                $operador = $regra['operador'] ?? '=';
                $valor = $regra['valor'] ?? '';

                $valores = array_map('trim', explode(',', (string)$valor));
                $condicaoStr = match ($operador) {
                    '=' => "Igual a '{$valor}'",
                    '!=' => "Diferente de '{$valor}'",
                    '>=' => "Maior ou igual a {$valor}",
                    '<=' => "Menor ou igual a {$valor}",
                    '>' => "Maior que {$valor}",
                    '<' => "Menor que {$valor}",
                    'between' => "Entre " . ($valores[0] ?? '') . " e " . ($valores[1] ?? ''),
                    'in' => "Dentre: " . implode(' ou ', $valores),
                    default => "{$operador} {$valor}"
                };

                $pontosStr = $tipo === 'multiplicador_percentual' ? "+{$pontos}%" : "+{$pontos} pts";

                $atendida = false;
                foreach ($auditoria as $aud) {
                    $campoAuditoria = strtolower(trim($aud['campo_avaliado'] ?? ''));
                    $campoAtual = strtolower(trim($campo));
                    
                    if ($campoAuditoria === $campoAtual || str_contains($campoAuditoria, $campoAtual) || str_contains($campoAtual, $campoAuditoria)) {
                        if ($aud['pontos_ganhos'] == $pontos) {
                            $condAudLimpa = str_replace('Exigência: ', '', $aud['condicao'] ?? '');
                            
                            if ($condAudLimpa === $condicaoStr || str_contains($condAudLimpa, $condicaoStr) || str_contains($condicaoStr, $condAudLimpa)) {
                                $atendida = true;
                                break;
                            }
                            
                            if (in_array($operador, ['between', 'in'])) {
                                $allMatched = true;
                                foreach ($valores as $v) {
                                    if (!str_contains($condAudLimpa, trim($v))) {
                                        $allMatched = false;
                                        break;
                                    }
                                }
                                if ($allMatched) {
                                    $atendida = true;
                                    break;
                                }
                            }

                            if (!empty($valor) && str_contains($condAudLimpa, (string)$valor)) {
                                $atendida = true;
                                break;
                            }
                        }
                    }
                }

                $bgClass = $atendida ? 'bg-green-50 border-green-200 dark:bg-green-900/30 dark:border-green-800' : 'bg-white border-gray-100 dark:bg-gray-700 dark:border-gray-600';
                $icon = $atendida ? '<i class="ph-fill ph-check-circle text-green-500 text-lg"></i>' : '<i class="ph ph-circle text-gray-300 text-lg"></i>';
                $textColor = $atendida ? 'text-green-800 dark:text-green-400' : 'text-gray-700 dark:text-gray-300';
                $ptsColor = $atendida ? 'text-green-700 bg-green-100 dark:bg-green-900/50 px-2 py-0.5 rounded' : 'text-gray-400';

                $html .= "<div class='flex items-center justify-between p-3 rounded-lg border {$bgClass}'>";
                $html .= "<div class='flex items-center gap-3'>";
                $html .= $icon;
                $html .= "<span class='block text-sm font-bold {$textColor}'>{$condicaoStr}</span>";
                $html .= "</div>";
                $html .= "<span class='font-black text-[10px] {$ptsColor}'>{$pontosStr}</span>";
                $html .= "</div>";
            }
            $html .= "</div></div>";
        }
        $html .= '</div>';

        if ($agrupadas->isEmpty()) {
            $html = '<div class="text-center py-8 text-gray-500"><i class="ph-fill ph-warning-circle text-4xl mb-2 text-gray-300"></i><p>Nenhuma regra configurada no ciclo.</p></div>';
        }

        $this->dispatch('load-quick-view', [
            'title' => 'Mapa de Regras: ' . $inscricao->nome,
            'subtitle' => 'Critérios avaliados e pontuados na auditoria',
            'icon' => 'ph-list-numbers',
            'maxWidth' => 'xl',
            'allowFullscreen' => true,
            'data' => [
                'Critérios e Acertos do Candidato' => $html
            ]
        ]);
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
                'Ações' => '
                    <div class="flex flex-col gap-2 mt-2">
                        <button @click="$dispatch(\'open-regras-crm\', { id: '.$id.' })" class="w-full text-left text-[11px] font-bold text-purpura-600 hover:text-purpura-800 bg-purpura-50 hover:bg-purpura-100 border border-purpura-200 px-3 py-2 rounded transition-colors uppercase tracking-wider inline-flex items-center gap-1.5 shadow-sm">
                            <i class="ph-bold ph-list-numbers text-sm"></i> Visualizar Acertos (Mapa de Regras)
                        </button>
                        <a href="'.route('inscricoes.show', $inscricao->id).'" target="_blank" class="w-full text-left text-[11px] font-bold text-gray-600 hover:text-gray-800 bg-gray-50 hover:bg-gray-100 border border-gray-200 px-3 py-2 rounded transition-colors uppercase tracking-wider inline-flex items-center gap-1.5 shadow-sm">
                            <i class="ph-bold ph-arrow-square-out text-sm"></i> Ficha Completa & Auditoria
                        </a>
                    </div>'
            ]
        ]);
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
            $this->reset(['selecionados', 'statusDestinoLote']);
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
        
        // CORREÇÃO UX: Se forem 10 ou menos registros (ex: Drag & Drop ou pequena seleção), 
        // executa de forma SÍNCRONA (imediata) para que a tela reaja instantaneamente.
        if ($qtd <= 10) {
            dispatch_sync(new \App\Jobs\ProcessarStatusEmLoteJob(null, $ids, $statusId));
            
            $this->reset(['selecionados', 'statusDestinoLote']);
            $this->dispatch('sucesso', msg: 'Status atualizado com sucesso!');
        } 
        // Se for um movimento em massa (Lote pesado), envia para a Nuvem (Assíncrono)
        else {
            $tracking = \App\Models\Importacao::create([
                'user_id' => auth()->id(), 'tipo' => 'inscricoes', 'operacao' => 'atualizacao_lote', 'formato' => 'system',
                'arquivo_nome' => "Alteração de Status via Fluxo: {$qtd} registros para '{$statusNovo->nome}'", 'status' => 'na_fila', 'total_linhas' => $qtd, 'linhas_processadas' => 0,
            ]);

            dispatch(new \App\Jobs\ProcessarStatusEmLoteJob($tracking->id, $ids, $statusId))->afterResponse();
            
            $this->reset(['selecionados', 'statusDestinoLote']);
            $this->dispatch('sucesso', msg: "Ação autorizada! {$qtd} registros enviados para processamento.");
        }
    }

    public function render()
    {
        $ciclo = Ciclo::with('statusPipeline')->find($this->cicloId);
        $colunas = $ciclo ? $ciclo->statusPipeline : collect();

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