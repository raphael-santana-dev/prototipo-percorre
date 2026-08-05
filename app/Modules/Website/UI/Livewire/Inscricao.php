<?php

namespace App\Modules\Website\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\Ciclo;
use App\Models\Inscricao as InscricaoModel; // Alias adicionado para evitar conflito com a classe do componente
use App\Models\User;
use App\Models\Unidade;
use App\Models\Curso;
use App\Models\Turno;
use App\Models\StatusInscricao;
use App\Models\RegraPontuacao;
use App\Traits\WithCepConsulta;

#[Layout('components.layouts.public')]
#[Title('Inscrição - Instituto Percorre')]
class Inscricao extends Component
{
    use WithCepConsulta;
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
            
            $maxEtapaDinamica = $this->camposDinamicos->max('etapa') ?? 1;
            $this->totalEtapas = max(1, $maxEtapaDinamica);

            foreach ($this->camposDinamicos as $campo) {
                if (!isset($this->respostas[$campo->name])) {
                    // A Matriz precisa nascer como Array para que o Livewire separe os Radio Buttons por linha!
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
            // $senhaProvisoria = Str::random(8);
            // $usuarioAluno = User::firstOrCreate(
            //     ['email' => $this->email],
            //     ['name' => $this->nome, 'password' => Hash::make($senhaProvisoria), 'precisa_trocar_senha' => true]
            // );

            // if ($usuarioAluno->wasRecentlyCreated) {
            //     $usuarioAluno->assignRole('aluno');
            //     \Illuminate\Support\Facades\Mail::to($usuarioAluno->email)
            //         ->send(new \App\Mail\BoasVindasAluno($usuarioAluno, $senhaProvisoria));
            // }
            // InscricaoModel::where('id', $this->inscricaoId)->update(['usuario_id' => $usuarioAluno->id]);
            
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
            'data_nascimento' => $this->data_nascimento,
            'unidade_id' => $this->unidade,
            'turno_id' => $this->turno,
            'curso_id' => $this->curso,
            'possui_deficiencia' => $this->possui_deficiencia,
            'natureza_deficiencia' => $this->natureza_deficiencia,
            'dados_dinamicos' => $this->respostas, 
        ];

        // SE FOR A ÚLTIMA ETAPA, CALCULA OS PONTOS!
        if ($this->etapaAtual === $this->totalEtapas && !$statusForcado) {
            $nomeStatus = 'Pendente'; // Status de finalização
            
            $pontuacao = $this->calcularPontuacaoAutomatica();
            $dados['pontuacao_total'] = $pontuacao['total'];
            $dados['pontuacao_detalhes'] = $pontuacao['detalhes']; // O Laravel fará o cast automático para JSON
            
        } elseif ($statusForcado) {
            $nomeStatus = ucfirst($statusForcado); 
        }

        // Busca o ID do Status no banco (Padrão 1 = Pendente)
        $statusDb = \App\Models\StatusInscricao::where('nome', $nomeStatus)->first();
        $dados['status_inscricao_id'] = $statusDb ? $statusDb->id : 1;

        $inscricao = \App\Models\Inscricao::updateOrCreate(['id' => $this->inscricaoId], $dados);
        if (!$this->inscricaoId) {
            $this->inscricaoId = $inscricao->id;
        }
    }

    public function restaurarDeDadosSalvos()
    {
        if ($this->estado && $this->data_nascimento) {
            $this->atualizarDisponibilidade();
            
            // Re-aplicando as seleções que vieram do Cache
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
                Rule::unique('inscricoes', 'cpf')->ignore($this->inscricaoId) // Restabelecida a regra do sistema antigo
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

    // VALIDAÇÃO EM TEMPO REAL
    public function updated($propertyName)
    {
        $regrasFinais = array_merge($this->rules(), $this->regrasPorEtapa($this->etapaAtual));
        
        // Verifica se a propriedade alterada existe nas regras (resolve validações aninhadas como respostas.*)
        if (str_starts_with($propertyName, 'respostas.')) {
            $this->validateOnly($propertyName, $regrasFinais);
        } elseif (array_key_exists($propertyName, $regrasFinais)) {
            $this->validateOnly($propertyName, $regrasFinais);
        }
    }

    // Atualização da Data de Nascimento (Restaurado do sistema antigo)
    public function updatedDataNascimento()
    {
        $this->atualizarDisponibilidade();
    }

    // =======================================================================
    // MOTOR DE DISPONIBILIDADE INTELIGENTE (Cascata Automática) 
    // Trazido do sistema antigo
    // =======================================================================
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
        
        // 1. Busca os cursos do Estado que atendem a idade e ESTÃO VINCULADOS AO CICLO ATUAL
        $cursosValidos = Curso::whereIn('status', ['Ativo', 'ativo', '1', true])
            ->whereHas('ciclos', function($q) {
                $q->where('ciclos.id', $this->cicloAtivoId);
            })
            ->where('min_idade', '<=', $idade)
            ->with(['unidades' => function($q) {
                $q->where('estado', $this->estado)->whereIn('status', ['Ativa', '1', true]);
            }])
            ->get();

        // 2. Extrai e remove as duplicadas das Unidades que possuem esses cursos
        $unidadesDisponiveisList = collect();
        foreach($cursosValidos as $curso) {
            foreach($curso->unidades as $unidade) {
                $unidadesDisponiveisList->put($unidade->id, $unidade->nome);
            }
        }

        if ($unidadesDisponiveisList->count() > 0) {
            $this->temVagasDisponiveis = true;
            $this->unidadesDisponiveis = $unidadesDisponiveisList->unique()->toArray();

            // Auto-select se houver apenas 1 Unidade
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
            ->whereIn('status', ['Ativo', 'ativo', '1', true])
            ->whereHas('ciclos', function($q) {
                $q->where('ciclos.id', $this->cicloAtivoId);
            })
            ->whereHas('unidades', function ($q) use ($unidadeId) {
                $q->where('unidades.id', $unidadeId);
            })
            ->where('min_idade', '<=', $idade)
            ->get();

        $this->cursosDisponiveis = $cursosDb->pluck('nome', 'id')->toArray();

        // Auto-select se houver apenas 1 Curso
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
            
            // Auto-select se houver apenas 1 Turno
            if (count($this->turnosDisponiveis) === 1) {
                $this->turno = array_key_first($this->turnosDisponiveis);
            }
        }
    }

    private function calcularPontuacaoAutomatica() 
    {
        $total = 0;
        $detalhes = ['auditoria_detalhada' => []];
        
        // Puxa as regras do JSON armazenado no Ciclo Ativo
        $regras = $this->cicloAtivo->regras_pontuacao ?? [];
        if (is_string($regras)) {
            $regras = json_decode($regras, true) ?? [];
        }

        if (empty($regras)) {
            return ['total' => 0, 'detalhes' => null];
        }

        foreach ($regras as $regra) {
            $campo = $regra['campo'];
            $valorResposta = null;

            // 1. Onde está a resposta? (Pode ser fixa, calculada ou dinâmica)
            if ($campo === 'idade' || !empty($this->data_nascimento)) {
                $valorResposta = \Carbon\Carbon::parse($this->data_nascimento)->age;
            } elseif (property_exists($this, $campo)) {
                $valorResposta = $this->$campo;
            } elseif (isset($this->respostas[$campo])) {
                $valorResposta = $this->respostas[$campo];
            }

            $pontuou = false;
            
            // 2. Realiza a Validação Matemática
            if ($valorResposta !== null && $valorResposta !== '') {
                $valorAlvoStr = $regra['valor'];
                
                if (in_array($regra['operador'], ['between', 'in'])) {
                    $valoresEsperados = array_map('trim', explode(',', $valorAlvoStr));
                } else {
                    $valoresEsperados = [trim($valorAlvoStr)];
                }
                
                $valorAlvo = $valoresEsperados[0] ?? null;

                switch ($regra['operador']) {
                    case '=': $pontuou = (strtolower(trim((string)$valorResposta)) === strtolower(trim((string)$valorAlvo))); break;
                    case '!=': $pontuou = (strtolower(trim((string)$valorResposta)) !== strtolower(trim((string)$valorAlvo))); break;
                    case '>=': $pontuou = ((float)$valorResposta >= (float)$valorAlvo); break;
                    case '<=': $pontuou = ((float)$valorResposta <= (float)$valorAlvo); break;
                    case 'between':
                        $min = (float)($valoresEsperados[0] ?? 0);
                        $max = (float)($valoresEsperados[1] ?? $min);
                        $pontuou = ((float)$valorResposta >= $min && (float)$valorResposta <= $max);
                        break;
                    case 'in':
                        $respostasValidas = array_map(fn($v) => strtolower(trim((string)$v)), $valoresEsperados);
                        $pontuou = in_array(strtolower(trim((string)$valorResposta)), $respostasValidas);
                        break;
                }
            }

            // 3. Registra os pontos e a auditoria caso a regra seja atendida
            if ($pontuou) {
                $total += (int) $regra['pontos'];
                // Usamos [] para permitir que o mesmo campo ganhe pontos por regras diferentes
                $detalhes['auditoria_detalhada'][] = [
                    'campo_avaliado' => $campo,
                    'resposta_dada' => $valorResposta,
                    'pontos_ganhos' => (int) $regra['pontos'],
                    'condicao' => "{$regra['operador']} " . implode(', ', $valoresEsperados ?? [])
                ];
            }
        }

        if ($total > 0) {
            $detalhes['motivo_auditoria'] = "Avaliação automática concluída. Total: {$total} pontos em " . count($detalhes['auditoria_detalhada']) . " regras atingidas.";
        } else {
            $detalhes = null; // Não salva JSON à toa se não pontuou nada
        }

        return [
            'total' => $total,
            'detalhes' => $detalhes
        ];
    }

    public function render()
    {
        return view('livewire.website.inscricao');
    }
}