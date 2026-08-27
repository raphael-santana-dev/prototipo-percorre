<?php

namespace App\Modules\Website\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\Ciclo;
use App\Models\Inscricao as InscricaoModel;
use App\Models\Curso;

#[Layout('components.layouts.public')]
#[Title('Inscrição - Instituto Percorre')]
class Inscricao extends Component
{
    use \App\Traits\WithCepConsulta;

    public int $etapaAtual = 1;
    public int $totalEtapas = 1;
    public $inscricaoId = null;
    public $cicloAtivoId = null;
    public $inscricoesAbertas = false;

    // Campos estáticos - Etapa 1
    public $nome, $nome_social, $cpf, $email, $celular, $data_nascimento, $cep, $logradouro, $bairro, $cidade, $estado, $numero, $complemento, $unidade, $turno, $curso, $natureza_deficiencia;
    public $possui_deficiencia = 'nao';
    public $possui_nome_social = 'nao';
    public $autorizacao_uso_infos = false;

    public $temVagasDisponiveis = true;
    public $camposDinamicos = [];
    public $respostas = [];

    // Arrays de Cascata
    public array $unidadesDisponiveis = [];
    public array $turnosDisponiveis = [];
    public array $cursosDisponiveis = [];
    
    // Configurações do Form Builder
    public array $formSettings = [];
    public bool $use_vacancy_limit = false; // Controle da Trava

    public function mount()
    {
        $ciclo = Ciclo::with(['campos' => function($query) {
            $query->orderBy('etapa', 'asc')->orderBy('ordem', 'asc');
        }])->where('status', true)
            ->where('data_inicio', '<=', now())
            ->where('data_fim', '>=', now())
            ->first();

        if ($ciclo) {
            $this->cicloAtivoId = $ciclo->id;
            $this->inscricoesAbertas = true;
            $this->camposDinamicos = $ciclo->campos;

            $cfg = $this->camposDinamicos->firstWhere('name', '_form_config');
            if ($cfg && $cfg->configuracoes) {
                $this->formSettings = is_string($cfg->configuracoes) ? json_decode($cfg->configuracoes, true) : $cfg->configuracoes;
                // Carrega a configuração da trava
                if (isset($this->formSettings['use_vacancy_limit'])) {
                    $this->use_vacancy_limit = filter_var($this->formSettings['use_vacancy_limit'], FILTER_VALIDATE_BOOLEAN);
                }
            }
            
            $maxEtapaDinamica = $this->camposDinamicos->max('etapa') ?? 1;
            $this->totalEtapas = max(1, $maxEtapaDinamica);

            foreach ($this->camposDinamicos as $campo) {
                if (!isset($this->respostas[$campo->name])) {
                    if (in_array($campo->tipo, ['check', 'matriz'])) {
                        $this->respostas[$campo->name] = [];
                    } else {
                        $this->respostas[$campo->name] = '';
                    }
                }
            }
        }
    }

    protected $messages = [
        'nome.required' => 'O preenchimento do nome é obrigatório.',
        'email.required' => 'O preenchimento do email é obrigatório.',
        'email.email' => 'Informe um email válido.',
        'cpf.required' => 'O preenchimento do CPF é obrigatório.',
        'cpf.unique' => 'Este CPF já está cadastrado em nosso sistema.',
        'nome_social.required_if' => 'Preencha o nome social ou marque "Não".',
        'autorizacao_uso_infos.accepted' => 'Você precisa aceitar os termos para concluir a inscrição.',
        'cep.required' => 'O CEP é obrigatório.',
        'data_nascimento.required' => 'A data de nascimento é obrigatória.',
    ];

    /**
     * MOTOR ULTRA RÁPIDO DE CHECAGEM DE VAGAS
     * Retorna um Array associativo apenas com as combinações (Unidade-Curso-Turno)
     * que ainda possuem vagas > inscritos aprovados.
     */
    private function getOfertasValidas()
    {
        if (!$this->use_vacancy_limit) return null; // Retorna null se a trava estiver desligada no construtor

        $ofertas = \App\Models\OfertaVaga::where('ciclo_id', $this->cicloAtivoId)->get();

        // Faz 1 única query GROUP BY para contar todas as inscrições aprovadas do ciclo!
        $ocupadas = InscricaoModel::selectRaw('curso_id, unidade_id, turno_id, count(*) as total')
            ->where('ciclo_id', $this->cicloAtivoId)
            ->whereHas('statusInscricao', function($q) {
                // Considera a vaga ocupada se o status for de aprovação
                $q->whereIn('nome', ['Aprovado', 'aprovado', 'Selecionado', 'selecionado']);
            })
            ->groupBy('curso_id', 'unidade_id', 'turno_id')
            ->get()
            ->keyBy(function($item) {
                return "{$item->unidade_id}-{$item->curso_id}-{$item->turno_id}";
            });

        $validas = [];
        foreach ($ofertas as $oferta) {
            $key = "{$oferta->unidade_id}-{$oferta->curso_id}-{$oferta->turno_id}";
            $qtdOcupada = isset($ocupadas[$key]) ? $ocupadas[$key]->total : 0;
            
            // Só disponibiliza se a quantidade de vagas da matriz for maior que o preenchido
            if ($oferta->vagas > $qtdOcupada) {
                $validas[$key] = true;
            }
        }
        return $validas;
    }

    protected function regrasPorEtapa($etapa)
    {
        $regras = [];
        if ($etapa === 1) {
            if ($this->temVagasDisponiveis && $this->estado && $this->data_nascimento) {
                $regras['unidade'] = 'required';
                $regras['turno'] = 'required';
                $regras['curso'] = 'required';
            }
        }
        if ($etapa === $this->totalEtapas) {
            $regras['autorizacao_uso_infos'] = 'accepted';
        }
        if ($this->camposDinamicos) {
            foreach ($this->camposDinamicos->where('etapa', $etapa) as $campo) {
                if (!empty($campo->depende_de) && !empty($campo->depende_valor)) {
                    $valorGatilho = $this->respostas[$campo->depende_de] ?? $this->{$campo->depende_de} ?? null;
                    $val = strtolower(trim((string)$valorGatilho));
                    $tgt = strtolower(trim((string)$campo->depende_valor));
                    $op = $campo->depende_operador ?? '=';
                    $condicaoAtendida = false;

                    switch($op) {
                        case '=': $condicaoAtendida = ($val === $tgt); break;
                        case '!=': $condicaoAtendida = ($val !== $tgt); break;
                        case '>': $condicaoAtendida = (is_numeric($val) && is_numeric($tgt) && $val > $tgt); break;
                        case '<': $condicaoAtendida = (is_numeric($val) && is_numeric($tgt) && $val < $tgt); break;
                        case '>=': $condicaoAtendida = (is_numeric($val) && is_numeric($tgt) && $val >= $tgt); break;
                        case '<=': $condicaoAtendida = (is_numeric($val) && is_numeric($tgt) && $val <= $tgt); break;
                        case 'in': 
                            $arrayAlvos = array_map('trim', explode(',', $tgt));
                            $condicaoAtendida = in_array($val, $arrayAlvos);
                            break;
                    }
                    if (!$condicaoAtendida) continue; 
                }

                $ruleStr = [];
                if ($campo->obrigatorio) $ruleStr[] = 'required';
                else $ruleStr[] = 'nullable';

                if ($campo->subtipo === 'email') $ruleStr[] = 'email';
                if ($campo->subtipo === 'number') $ruleStr[] = 'numeric';
                if ($campo->subtipo === 'date') $ruleStr[] = 'date';
                if ($campo->tamanho_min !== null) $ruleStr[] = 'min:'.$campo->tamanho_min;
                if ($campo->tamanho_max !== null) $ruleStr[] = 'max:'.$campo->tamanho_max;
                if (!empty($campo->regras_validacao)) $ruleStr = array_merge($ruleStr, explode('|', $campo->regras_validacao));
                
                $regras['respostas.' . $campo->name] = implode('|', $ruleStr);
            }
        }
        return $regras;
    }

    public function avancarEtapa()
    {
        // 1. Validações padrão de campos obrigatórios (Front-end/Formulário)
        $regrasFinais = array_merge($this->rules(), $this->regrasPorEtapa($this->etapaAtual));
        
        $this->validate($regrasFinais, [
            'autorizacao_uso_infos.accepted' => 'Você precisa aceitar os termos.',
            'respostas.*.required' => 'Este campo é obrigatório.'
        ]);

        // =========================================================
        // ETAPA 4: TRAVA DE OVERBOOKING (CONCORRÊNCIA EM TEMPO REAL)
        // =========================================================
        if ($this->etapaAtual === 1 && $this->temVagasDisponiveis && $this->use_vacancy_limit) {
            $ofertasValidas = $this->getOfertasValidas();
            
            // Verifica se a combinação escolhida pelo aluno ainda está na lista de válidas
            if ($ofertasValidas !== null && $this->unidade && $this->curso && $this->turno) {
                $key = "{$this->unidade}-{$this->curso}-{$this->turno}";
                
                if (!isset($ofertasValidas[$key])) {
                    // A vaga foi ocupada enquanto ele preenchia os dados!
                    $this->addError('curso', 'As vagas para esta opção acabaram de se esgotar! Por favor, escolha outro curso, turno ou unidade.');
                    $this->addError('turno', 'Vagas esgotadas.');
                    
                    // Recarrega as opções disponíveis na tela, sumindo com a opção esgotada
                    $this->atualizarDisponibilidade(); 
                    
                    // Trava a execução, impedindo que a inscrição seja salva com essa combinação
                    return; 
                }
            }
        }

        // 2. Verifica se é um "Lead" (Sem vagas para a idade dele logo de cara)
        if ($this->etapaAtual === 1) {
            if (!$this->temVagasDisponiveis && $this->data_nascimento && $this->estado) {
                $this->salvarProgresso('Lead'); 
                $this->etapaAtual = 100; 
                $this->dispatch('inscricao-concluida'); 
                return;
            }
        }

        // 3. Salva o rascunho (ou a inscrição final)
        $this->salvarProgresso();

        // 4. Avança a página ou finaliza
        if ($this->etapaAtual < $this->totalEtapas) {
            $this->etapaAtual++;
        } else {            
            $this->etapaAtual = 99; 
            $this->dispatch('inscricao-concluida');
        }
    }

    private function salvarProgresso($statusForcado = null)
    {
        $nomeStatus = 'Incompleto'; 
        $dados = [
            'ciclo_id' => $this->cicloAtivoId,
            'etapa_atual' => $this->etapaAtual,
            'nome' => $this->nome,
            'possui_nome_social' => $this->possui_nome_social,
            'nome_social' => $this->nome_social,
            'cpf' => $this->cpf,
            'email' => $this->email,
            'celular' => $this->celular,
            'data_nascimento' => $this->data_nascimento,
            'cep' => $this->cep,
            'logradouro' => $this->logradouro,
            'numero' => $this->numero,
            'complemento' => $this->complemento,
            'bairro' => $this->bairro,
            'cidade' => $this->cidade,
            'estado' => $this->estado,
            'unidade_id' => $this->unidade,
            'turno_id' => $this->turno,
            'curso_id' => $this->curso,
            'possui_deficiencia' => $this->possui_deficiencia,
            'natureza_deficiencia' => $this->natureza_deficiencia,
            'autorizacao_uso_infos' => $this->autorizacao_uso_infos ? 1 : 0,
            'dados_dinamicos' => $this->respostas, 
            'slug' => Str::slug($this->nome),
        ];

        if ($statusForcado) {
            $nomeStatus = ucfirst($statusForcado); 
        } elseif ($this->etapaAtual === $this->totalEtapas) {
            $nomeStatus = 'Pendente'; 
        }

        if ($this->etapaAtual === $this->totalEtapas || $statusForcado === 'Lead') {
            $pontuacao = $this->calcularPontuacaoAutomatica();
            $dados['pontuacao_total'] = $pontuacao['total'];
            $dados['pontuacao_detalhes'] = $pontuacao['detalhes']; 
        }

        $statusDb = \App\Models\StatusInscricao::where('nome', $nomeStatus)->first();
        $dados['status_inscricao_id'] = $statusDb ? $statusDb->id : 1;

        $inscricao = InscricaoModel::updateOrCreate(['id' => $this->inscricaoId], $dados);
        if (!$this->inscricaoId) {
            $this->inscricaoId = $inscricao->id;
        }
    }

    public function restaurarDeDadosSalvos()
    {
        if ($this->estado && $this->data_nascimento) {
            $this->atualizarDisponibilidade();
            if ($this->unidade) $this->updatedUnidade($this->unidade);
            if ($this->curso) $this->updatedCurso($this->curso);
        }
    }

    protected function rules()
    {
        return [
            'nome' => 'required|min:3',
            'email' => 'required|email',
            'cpf' => [
                'required',
                function ($attribute, $value, $fail) {
                    $c = preg_replace('/\D/', '', $value);
                    if (strlen($c) != 11 || preg_match("/^{$c[0]}{11}$/", $c)) return $fail('O CPF informado é inválido.');
                    for ($s = 10, $n = 0, $i = 0; $s >= 2; $n += $c[$i++] * $s--);
                    if ($c[9] != ((($n %= 11) < 2) ? 0 : 11 - $n)) return $fail('O CPF informado é inválido.');
                    for ($s = 11, $n = 0, $i = 0; $s >= 2; $n += $c[$i++] * $s--);
                    if ($c[10] != ((($n %= 11) < 2) ? 0 : 11 - $n)) return $fail('O CPF informado é inválido.');
                },
                Rule::unique('inscricoes', 'cpf')->ignore($this->inscricaoId) 
            ],
            'nome_social' => 'required_if:possui_nome_social,sim',
            'possui_deficiencia' => 'required',
            'cep' => 'required',
            'data_nascimento' => 'required|date',
        ];
    }

    public function voltarEtapa() { if ($this->etapaAtual > 1) $this->etapaAtual--; }

    public function updated($propertyName)
    {
        $regrasFinais = array_merge($this->rules(), $this->regrasPorEtapa($this->etapaAtual));
        if (str_starts_with($propertyName, 'respostas.')) {
            $this->validateOnly($propertyName, $regrasFinais);
        } elseif (array_key_exists($propertyName, $regrasFinais)) {
            $this->validateOnly($propertyName, $regrasFinais);
        }
    }

    public function updatedDataNascimento() { $this->atualizarDisponibilidade(); }

    public function atualizarDisponibilidade()
    {
        $this->unidadesDisponiveis = [];
        $this->cursosDisponiveis = [];
        $this->turnosDisponiveis = [];
        $this->unidade = null;
        $this->curso = null;
        $this->turno = null;
        $this->temVagasDisponiveis = false;

        if (!$this->estado || !$this->data_nascimento) return;

        $idade = Carbon::parse($this->data_nascimento)->age;
        $ofertasValidas = $this->getOfertasValidas();
        
        // NOVO MOTOR DE BUSCA: Lendo as idades diretamente da tabela de Ofertas (ofertas_vagas)
        $ofertasDisponiveis = \App\Models\OfertaVaga::with(['curso', 'unidade', 'turno'])
            ->where('ciclo_id', $this->cicloAtivoId)
            ->where(function($q) use ($idade) {
                $q->whereNull('idade_min')->orWhere('idade_min', '<=', $idade);
            })
            ->where(function($q) use ($idade) {
                $q->whereNull('idade_max')->orWhere('idade_max', '>=', $idade);
            })
            ->get();

        $unidadesDisponiveisList = collect();
        
        foreach($ofertasDisponiveis as $oferta) {
            // Verifica status do Curso e Unidade
            if (!in_array($oferta->curso->status, ['Ativo', 'ativo', '1', 1, true])) continue;
            if (!in_array($oferta->unidade->status, ['Ativa', 'ativa', '1', 1, true])) continue;
            
            // Verifica bloqueio de Estado (UF) do curso
            if (!$oferta->curso->permite_estado_diferente && $oferta->unidade->estado !== $this->estado) continue;

            // Verifica a trava de vagas (overbooking)
            if ($this->use_vacancy_limit && $ofertasValidas !== null) {
                $key = "{$oferta->unidade_id}-{$oferta->curso_id}-{$oferta->turno_id}";
                if (!isset($ofertasValidas[$key])) continue;
            }

            $unidadesDisponiveisList->put($oferta->unidade_id, $oferta->unidade->nome);
        }

        if ($unidadesDisponiveisList->count() > 0) {
            $this->temVagasDisponiveis = true;
            $this->unidadesDisponiveis = $unidadesDisponiveisList->unique()->toArray();

            if (count($this->unidadesDisponiveis) === 1) {
                $this->unidade = array_key_first($this->unidadesDisponiveis);
                $this->updatedUnidade($this->unidade);
            }
        } else {
            $this->temVagasDisponiveis = false;
        }
    }

    public function updatedUnidade($unidadeId)
    {
        $this->curso = null;
        $this->turno = null;
        $this->cursosDisponiveis = [];
        $this->turnosDisponiveis = [];

        if (!$unidadeId || !$this->data_nascimento) return;

        $idade = Carbon::parse($this->data_nascimento)->age;
        $ofertasValidas = $this->getOfertasValidas();

        $ofertasDaUnidade = \App\Models\OfertaVaga::with(['curso', 'turno'])
            ->where('ciclo_id', $this->cicloAtivoId)
            ->where('unidade_id', $unidadeId)
            ->where(function($q) use ($idade) {
                $q->whereNull('idade_min')->orWhere('idade_min', '<=', $idade);
            })
            ->where(function($q) use ($idade) {
                $q->whereNull('idade_max')->orWhere('idade_max', '>=', $idade);
            })
            ->orderBy('nome', asc)
            ->get();

        $unidadeSelecionada = \App\Modules\Unidade\Domain\Models\Unidade::find($unidadeId);

        foreach ($ofertasDaUnidade as $oferta) {
            if (!in_array($oferta->curso->status, ['Ativo', 'ativo', '1', 1, true])) continue;
            if (!$oferta->curso->permite_estado_diferente && $unidadeSelecionada && $unidadeSelecionada->estado !== $this->estado) continue;

            if ($this->use_vacancy_limit && $ofertasValidas !== null) {
                $key = "{$unidadeId}-{$oferta->curso_id}-{$oferta->turno_id}";
                if (!isset($ofertasValidas[$key])) continue;
            }

            $this->cursosDisponiveis[$oferta->curso_id] = $oferta->curso->nome;
        }

        if (count($this->cursosDisponiveis) === 1) {
            $this->curso = array_key_first($this->cursosDisponiveis);
            $this->updatedCurso($this->curso);
        }
    }

    public function updatedCurso($cursoId)
    {
        $this->turno = null;
        $this->turnosDisponiveis = [];

        if (!$cursoId || !$this->unidade || !$this->data_nascimento) return;

        $idade = Carbon::parse($this->data_nascimento)->age;
        $ofertasValidas = $this->getOfertasValidas();

        $ofertasDoCurso = \App\Models\OfertaVaga::with('turno')
            ->where('ciclo_id', $this->cicloAtivoId)
            ->where('unidade_id', $this->unidade)
            ->where('curso_id', $cursoId)
            ->where(function($q) use ($idade) {
                $q->whereNull('idade_min')->orWhere('idade_min', '<=', $idade);
            })
            ->where(function($q) use ($idade) {
                $q->whereNull('idade_max')->orWhere('idade_max', '>=', $idade);
            })
            ->get();

        foreach ($ofertasDoCurso as $oferta) {
            if ($this->use_vacancy_limit && $ofertasValidas !== null) {
                $key = "{$this->unidade}-{$cursoId}-{$oferta->turno_id}";
                if (!isset($ofertasValidas[$key])) continue;
            }
            $this->turnosDisponiveis[$oferta->turno_id] = $oferta->turno->nome;
        }
        
        if (count($this->turnosDisponiveis) === 1) {
            $this->turno = array_key_first($this->turnosDisponiveis);
        }
    }

    private function calcularPontuacaoAutomatica() 
    {
        $scoreBase = 0;
        $scoreBonus = 0;
        $acertosPadrao = 0;
        $detalhes = ['auditoria_detalhada' => []];
        
        $ciclo = Ciclo::find($this->cicloAtivoId);
        $regras = $ciclo ? $ciclo->regras_pontuacao : [];

        if (is_string($regras)) $regras = json_decode($regras, true) ?? [];
        if (empty($regras) || !is_array($regras)) return ['total' => 0, 'detalhes' => null];

        $avaliarCondicao = function($regra) {
            if (($regra['escopo'] ?? 'especifico') === 'todos' && ($regra['tipo_regra'] ?? 'padrao') !== 'padrao') return true; 

            $campo = trim($regra['campo'] ?? '');
            $operador = trim($regra['operador'] ?? '=');
            $valorResposta = null;

            if ($campo === 'idade' && !empty($this->data_nascimento)) {
                $valorResposta = Carbon::parse($this->data_nascimento)->age;
            } elseif ($campo === 'curso_id') {
                $valorResposta = $this->curso;
            } elseif ($campo === 'turno_id') {
                $valorResposta = $this->turno;
            } elseif ($campo === 'unidade_id') {
                $valorResposta = $this->unidade;
            } elseif (property_exists($this, $campo)) {
                $valorResposta = $this->$campo;
            } elseif (isset($this->respostas[$campo])) {
                $valorResposta = $this->respostas[$campo];
            }

            if ($valorResposta === null || $valorResposta === '') return false;

            $valorAlvoStr = trim((string)($regra['valor'] ?? ''));
            $valoresEsperados = in_array($operador, ['between', 'in']) ? array_map('trim', explode(',', $valorAlvoStr)) : [$valorAlvoStr];
            $valorAlvo = $valoresEsperados[0] ?? null;

            switch ($operador) {
                case '=': return (strtolower(trim((string)$valorResposta)) === strtolower(trim((string)$valorAlvo)));
                case '!=': return (strtolower(trim((string)$valorResposta)) !== strtolower(trim((string)$valorAlvo)));
                case '>=': return ((float)$valorResposta >= (float)$valorAlvo);
                case '<=': return ((float)$valorResposta <= (float)$valorAlvo);
                case '>': return ((float)$valorResposta > (float)$valorAlvo);
                case '<': return ((float)$valorResposta < (float)$valorAlvo);
                case 'between':
                    $min = (float)($valoresEsperados[0] ?? 0);
                    $max = (float)($valoresEsperados[1] ?? $min);
                    return ((float)$valorResposta >= $min && (float)$valorResposta <= $max);
                case 'in':
                    $respostasValidas = array_map(fn($v) => strtolower(trim((string)$v)), $valoresEsperados);
                    return in_array(strtolower(trim((string)$valorResposta)), $respostasValidas);
            }
            return false;
        };

        foreach ($regras as $regra) {
            $tipo = $regra['tipo_regra'] ?? 'padrao';
            if ($tipo === 'padrao' && $avaliarCondicao($regra)) {
                $pontos = (float) ($regra['pontos'] ?? 0);
                $scoreBase += $pontos;
                $acertosPadrao++;
                
                $detalhes['auditoria_detalhada'][] = [
                    'tipo_regra' => 'padrao', 'campo_avaliado' => $regra['campo'], 'resposta_dada' => "Condição atendida", 'pontos_ganhos' => $pontos, 'condicao' => "{$regra['operador']} {$regra['valor']}"
                ];
            }
        }

        foreach ($regras as $regra) {
            $tipo = $regra['tipo_regra'] ?? 'padrao';
            $escopo = $regra['escopo'] ?? 'especifico';
            if ($tipo !== 'padrao' && $avaliarCondicao($regra)) {
                $multiplicador = (float) ($regra['pontos'] ?? 0);
                $pontosGanhos = 0;
                $motivo = "";

                if ($tipo === 'bonus_por_acerto') {
                    $pontosGanhos = $multiplicador * $acertosPadrao; 
                    $motivo = "Bônus (+{$multiplicador} pts) multiplicado por {$acertosPadrao} acertos base.";
                } elseif ($tipo === 'multiplicador_percentual') {
                    $pontosGanhos = $scoreBase * ($multiplicador / 100); 
                    $motivo = "Bônus de {$multiplicador}% aplicado sobre Score Base ({$scoreBase} pts).";
                }

                if ($pontosGanhos > 0) {
                    $scoreBonus += $pontosGanhos;
                    $detalhes['auditoria_detalhada'][] = [
                        'tipo_regra' => 'especial', 'campo_avaliado' => ($escopo === 'todos') ? 'Regra Global' : $regra['campo'], 'resposta_dada' => "Benefício Ativado", 'pontos_ganhos' => $pontosGanhos, 'condicao' => $motivo
                    ];
                }
            }
        }

        $totalFinal = $scoreBase + $scoreBonus;
        if ($totalFinal > 0) {
            $detalhes['motivo_auditoria'] = "Avaliação automática. Base: {$scoreBase} pts. Bônus: {$scoreBonus} pts. Total: {$totalFinal} pts.";
        } else {
            $detalhes = null; 
        }

        return ['total' => $totalFinal, 'detalhes' => $detalhes];
    }

    public function render()
    {
        return view('livewire.website.inscricao');
    }
}