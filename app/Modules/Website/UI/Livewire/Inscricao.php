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

    // Arrays de Cascata trazidos do sistema antigo
    public array $unidadesDisponiveis = [];
    public array $turnosDisponiveis = [];
    public array $cursosDisponiveis = [];
    public array $formSettings = [];

    public function mount()
    {
        $ciclo = Ciclo::with('campos')->where('status', true)
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

                if (!empty($campo->regras_validacao)) {
                    $ruleStr = array_merge($ruleStr, explode('|', $campo->regras_validacao));
                }
                
                $regras['respostas.' . $campo->name] = implode('|', $ruleStr);
            }
        }

        return $regras;
    }

    public function avancarEtapa()
    {
        $regrasFinais = array_merge($this->rules(), $this->regrasPorEtapa($this->etapaAtual));
        
        $this->validate($regrasFinais, [
            'autorizacao_uso_infos.accepted' => 'Você precisa aceitar os termos.',
            'respostas.*.required' => 'Este campo é obrigatório.'
        ]);

        if ($this->etapaAtual === 1) {
            if (!$this->temVagasDisponiveis && $this->data_nascimento && $this->estado) {
                $this->salvarProgresso('Lead'); 
                $this->etapaAtual = 100; 
                $this->dispatch('inscricao-concluida'); 
                return;
            }
        }

        $this->salvarProgresso();

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

        // CALCULA OS PONTOS (Seja finalização normal OU indo para a Lista de Espera)
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

    public function voltarEtapa()
    {
        if ($this->etapaAtual > 1) {
            $this->etapaAtual--;
        }
    }

    public function updated($propertyName)
    {
        $regrasFinais = array_merge($this->rules(), $this->regrasPorEtapa($this->etapaAtual));
        
        if (str_starts_with($propertyName, 'respostas.')) {
            $this->validateOnly($propertyName, $regrasFinais);
        } elseif (array_key_exists($propertyName, $regrasFinais)) {
            $this->validateOnly($propertyName, $regrasFinais);
        }
    }

    public function updatedDataNascimento()
    {
        $this->atualizarDisponibilidade();
    }

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
        
        $cursosValidos = Curso::whereIn('status', ['Ativo', 'ativo', '1', 1, true])
            ->whereHas('ciclos', function($q) {
                $q->where('ciclos.id', $this->cicloAtivoId);
            })
            ->where(function($q) use ($idade) {
                $q->whereNull('min_idade')->orWhere('min_idade', '<=', $idade);
            })
            ->where(function($q) use ($idade) {
                $q->whereNull('max_idade')->orWhere('max_idade', '>=', $idade);
            })
            ->with(['unidades' => function($q) {
                $q->whereIn('status', ['Ativa', 'ativa', '1', 1, true]);
            }])
            ->get();

        $unidadesDisponiveisList = collect();
        
        foreach($cursosValidos as $curso) {
            foreach($curso->unidades as $unidade) {
                if (!$curso->permite_estado_diferente && $unidade->estado !== $this->estado) {
                    continue;
                }
                $unidadesDisponiveisList->put($unidade->id, $unidade->nome);
            }
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

        $cursosDb = Curso::query()
            ->whereIn('status', ['Ativo', 'ativo', '1', 1, true])
            ->whereHas('ciclos', function($q) {
                $q->where('ciclos.id', $this->cicloAtivoId);
            })
            ->whereHas('unidades', function ($q) use ($unidadeId) {
                $q->where('unidades.id', $unidadeId);
            })
            ->where(function($q) use ($idade) {
                $q->whereNull('min_idade')->orWhere('min_idade', '<=', $idade);
            })
            ->where(function($q) use ($idade) {
                $q->whereNull('max_idade')->orWhere('max_idade', '>=', $idade);
            })
            ->get();

        $unidadeSelecionada = \App\Modules\Unidade\Domain\Models\Unidade::find($unidadeId);

        foreach ($cursosDb as $curso) {
            if (!$curso->permite_estado_diferente && $unidadeSelecionada && $unidadeSelecionada->estado !== $this->estado) {
                continue;
            }
            $this->cursosDisponiveis[$curso->id] = $curso->nome;
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

        if (!$cursoId) return;

        $curso = Curso::find($cursoId);
        if ($curso) {
            $this->turnosDisponiveis = $curso->turnosVinculados()->pluck('nome', 'id')->toArray();
            
            if (count($this->turnosDisponiveis) === 1) {
                $this->turno = array_key_first($this->turnosDisponiveis);
            }
        }
    }

    // ====================================================================================
    // MOTOR DE PONTUAÇÃO (TWO-PASS EVALUATION)
    // ====================================================================================
    private function calcularPontuacaoAutomatica() 
    {
        $scoreBase = 0;
        $scoreBonus = 0;
        $acertosPadrao = 0;
        $detalhes = ['auditoria_detalhada' => []];
        
        $ciclo = Ciclo::find($this->cicloAtivoId);
        $regras = $ciclo ? $ciclo->regras_pontuacao : [];

        if (is_string($regras)) $regras = json_decode($regras, true) ?? [];
        if (is_string($regras)) $regras = json_decode($regras, true) ?? [];
        if (empty($regras) || !is_array($regras)) return ['total' => 0, 'detalhes' => null];

        // Closure para avaliar uma condição cruzando a regra vs as respostas do aluno
        $avaliarCondicao = function($regra) {
            // Se a regra é "Global" (Todos), ela não tem campo/valor para checar, a condição é aprovada automaticamente
            if (($regra['escopo'] ?? 'especifico') === 'todos' && ($regra['tipo_regra'] ?? 'padrao') !== 'padrao') {
                return true; 
            }

            $campo = trim($regra['campo'] ?? '');
            $operador = trim($regra['operador'] ?? '=');
            $valorResposta = null;

            // Mapeamento Blindado
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

        // PASSAGEM 1: Calcular apenas as Regras "Padrão" para obter a Base e o Nº de Acertos
        foreach ($regras as $regra) {
            $tipo = $regra['tipo_regra'] ?? 'padrao';
            
            if ($tipo === 'padrao') {
                if ($avaliarCondicao($regra)) {
                    $pontos = (float) ($regra['pontos'] ?? 0);
                    $scoreBase += $pontos;
                    $acertosPadrao++;
                    
                    $detalhes['auditoria_detalhada'][] = [
                        'tipo_regra' => 'padrao',
                        'campo_avaliado' => $regra['campo'],
                        'resposta_dada' => "Condição atendida",
                        'pontos_ganhos' => $pontos,
                        'condicao' => "{$regra['operador']} {$regra['valor']}"
                    ];
                }
            }
        }

        // PASSAGEM 2: Aplicar Regras Especiais (Bônus e Multiplicadores) baseadas no resultado da Passagem 1
        foreach ($regras as $regra) {
            $tipo = $regra['tipo_regra'] ?? 'padrao';
            $escopo = $regra['escopo'] ?? 'especifico';
            
            if ($tipo !== 'padrao') {
                if ($avaliarCondicao($regra)) {
                    $multiplicador = (float) ($regra['pontos'] ?? 0);
                    $pontosGanhos = 0;
                    $motivo = "";

                    if ($tipo === 'bonus_por_acerto') {
                        $pontosGanhos = $multiplicador * $acertosPadrao; // Ex: 5 pts x 3 acertos base
                        $motivo = "Bônus (+{$multiplicador} pts) multiplicado por {$acertosPadrao} acertos base.";
                    } elseif ($tipo === 'multiplicador_percentual') {
                        $pontosGanhos = $scoreBase * ($multiplicador / 100); // Ex: 10% sobre a base de 50
                        $motivo = "Bônus de {$multiplicador}% aplicado sobre Score Base ({$scoreBase} pts).";
                    }

                    if ($pontosGanhos > 0) {
                        $scoreBonus += $pontosGanhos;
                        
                        $alvoDescritivo = ($escopo === 'todos') ? 'Regra Global (Todos os Campos)' : $regra['campo'];
                        
                        $detalhes['auditoria_detalhada'][] = [
                            'tipo_regra' => 'especial',
                            'campo_avaliado' => $alvoDescritivo,
                            'resposta_dada' => "Benefício Ativado",
                            'pontos_ganhos' => $pontosGanhos,
                            'condicao' => $motivo
                        ];
                    }
                }
            }
        }

        $totalFinal = $scoreBase + $scoreBonus;

        if ($totalFinal > 0) {
            $detalhes['motivo_auditoria'] = "Avaliação automática (Formulário). Score Base: {$scoreBase} pts. Score Bônus: {$scoreBonus} pts. Total Consolidado: {$totalFinal} pts.";
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