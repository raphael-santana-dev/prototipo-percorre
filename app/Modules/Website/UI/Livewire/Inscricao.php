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

    public $nome, $nome_social, $cpf, $email, $celular, $data_nascimento, $cep, $logradouro, $bairro, $cidade, $estado, $numero, $complemento, $unidade, $turno, $curso, $natureza_deficiencia;
    public $possui_deficiencia = 'nao';
    public $possui_nome_social = 'nao';
    public $autorizacao_uso_infos = false;

    public $temVagasDisponiveis = true;
    public $camposDinamicos = [];
    public $respostas = [];

    public array $unidadesDisponiveis = [];
    public array $turnosDisponiveis = [];
    public array $cursosDisponiveis = [];
    
    public array $formSettings = [];
    public bool $use_vacancy_limit = false; 

    // NOVAS VARIÁVEIS PARA CAPTAÇÃO DE INTERESSE
    public bool $deseja_informar = false;
    public $unidade_interesse = null;
    public $curso_interesse = null;
    public $turno_interesse = null;

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
                if (isset($this->formSettings['use_vacancy_limit'])) {
                    $this->use_vacancy_limit = filter_var($this->formSettings['use_vacancy_limit'], FILTER_VALIDATE_BOOLEAN);
                }
            }
            
            $maxEtapaDinamica = $this->camposDinamicos->max('etapa') ?? 1;
            $this->totalEtapas = max(1, $maxEtapaDinamica);

            foreach ($this->camposDinamicos as $campo) {
                if (!isset($this->respostas[$campo->name])) {
                    $this->respostas[$campo->name] = in_array($campo->tipo, ['check', 'matriz', 'social']) ? [] : '';
                }
            }

            if (session()->has('inscricao_retomada_id')) {
                $inscricaoRetomada = InscricaoModel::find(session('inscricao_retomada_id'));
                
                if ($inscricaoRetomada && $inscricaoRetomada->ciclo_id == $this->cicloAtivoId) {
                    $this->inscricaoId = $inscricaoRetomada->id;
                    $this->etapaAtual = $inscricaoRetomada->etapa_atual ?? 1;
                    
                    if (in_array(strtolower(trim($inscricaoRetomada->statusInscricao->nome ?? '')), ['aprovado', 'reprovado', 'selecionado', 'cancelado'])) {
                        $this->etapaAtual = 99;
                        session()->forget('inscricao_retomada_id');
                        return;
                    }

                    $this->nome = $inscricaoRetomada->nome;
                    $this->cpf = $inscricaoRetomada->cpf;
                    $this->email = $inscricaoRetomada->email;
                    $this->celular = $inscricaoRetomada->celular;
                    $this->data_nascimento = $inscricaoRetomada->data_nascimento ? $inscricaoRetomada->data_nascimento->format('Y-m-d') : null;
                    $this->cep = $inscricaoRetomada->cep;
                    $this->logradouro = $inscricaoRetomada->logradouro;
                    $this->bairro = $inscricaoRetomada->bairro;
                    $this->cidade = $inscricaoRetomada->cidade;
                    $this->estado = $inscricaoRetomada->estado;
                    $this->numero = $inscricaoRetomada->numero;
                    $this->complemento = $inscricaoRetomada->complemento;
                    
                    $this->possui_nome_social = $inscricaoRetomada->possui_nome_social ?? 'nao';
                    $this->nome_social = $inscricaoRetomada->nome_social;
                    $this->possui_deficiencia = $inscricaoRetomada->possui_deficiencia ?? 'nao';
                    $this->natureza_deficiencia = $inscricaoRetomada->natureza_deficiencia;
                    $this->autorizacao_uso_infos = (bool) $inscricaoRetomada->autorizacao_uso_infos;

                    $this->unidade = $inscricaoRetomada->unidade_id;
                    $this->curso = $inscricaoRetomada->curso_id;
                    $this->turno = $inscricaoRetomada->turno_id;

                    // RECUPERA DADOS DE INTERESSE
                    $this->deseja_informar = (bool) $inscricaoRetomada->deseja_informar;
                    $this->unidade_interesse = $inscricaoRetomada->unidade_interesse_id;
                    $this->curso_interesse = $inscricaoRetomada->curso_interesse_id;
                    $this->turno_interesse = $inscricaoRetomada->turno_interesse_id;

                    $dadosAntigos = is_string($inscricaoRetomada->dados_dinamicos) ? json_decode($inscricaoRetomada->dados_dinamicos, true) : $inscricaoRetomada->dados_dinamicos;
                    if (is_array($dadosAntigos)) {
                        foreach ($dadosAntigos as $chave => $valor) {
                            $this->respostas[$chave] = $valor;
                        }
                    }

                    $this->restaurarDeDadosSalvos();
                }
                
                session()->forget('inscricao_retomada_id');
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

    public function updatedCpf($value)
    {
        $cpfLimpo = preg_replace('/[^0-9]/', '', $value);
        
        if (strlen($cpfLimpo) === 11) {
            $inscricaoRetomada = InscricaoModel::with('statusInscricao')
                ->where('cpf', $cpfLimpo)
                ->where('ciclo_id', $this->cicloAtivoId)
                ->first();

            if ($inscricaoRetomada) {
                if (in_array(strtolower(trim($inscricaoRetomada->statusInscricao->nome ?? '')), ['aprovado', 'reprovado', 'selecionado', 'cancelado', 'pendente'])) {
                    $this->addError('cpf', 'Este CPF já possui uma inscrição finalizada neste ciclo.');
                    return;
                }

                $this->inscricaoId = $inscricaoRetomada->id;
                $this->etapaAtual = $inscricaoRetomada->etapa_atual ?? 1;
                $this->nome = $inscricaoRetomada->nome;
                $this->email = $inscricaoRetomada->email;
                $this->celular = $inscricaoRetomada->celular;
                $this->data_nascimento = $inscricaoRetomada->data_nascimento ? $inscricaoRetomada->data_nascimento->format('Y-m-d') : null;
                $this->cep = $inscricaoRetomada->cep;
                $this->logradouro = $inscricaoRetomada->logradouro;
                $this->bairro = $inscricaoRetomada->bairro;
                $this->cidade = $inscricaoRetomada->cidade;
                $this->estado = $inscricaoRetomada->estado;
                $this->numero = $inscricaoRetomada->numero;
                $this->complemento = $inscricaoRetomada->complemento;
                $this->possui_nome_social = $inscricaoRetomada->possui_nome_social ?? 'nao';
                $this->nome_social = $inscricaoRetomada->nome_social;
                $this->possui_deficiencia = $inscricaoRetomada->possui_deficiencia ?? 'nao';
                $this->natureza_deficiencia = $inscricaoRetomada->natureza_deficiencia;
                
                $this->unidade = $inscricaoRetomada->unidade_id;
                $this->curso = $inscricaoRetomada->curso_id;
                $this->turno = $inscricaoRetomada->turno_id;

                // RECUPERA DADOS DE INTERESSE
                $this->deseja_informar = (bool) $inscricaoRetomada->deseja_informar;
                $this->unidade_interesse = $inscricaoRetomada->unidade_interesse_id;
                $this->curso_interesse = $inscricaoRetomada->curso_interesse_id;
                $this->turno_interesse = $inscricaoRetomada->turno_interesse_id;

                $dadosAntigos = is_string($inscricaoRetomada->dados_dinamicos) ? json_decode($inscricaoRetomada->dados_dinamicos, true) : $inscricaoRetomada->dados_dinamicos;
                if (is_array($dadosAntigos)) {
                    foreach ($dadosAntigos as $chave => $valor) {
                        $this->respostas[$chave] = $valor;
                    }
                }

                $this->restaurarDeDadosSalvos();

                $this->dispatch('sucesso', msg: 'Encontramos uma inscrição em andamento! Recuperamos seus dados de onde você parou.');
            }
        }
    }

    private function getOfertasValidas()
    {
        if (!$this->use_vacancy_limit) return null;

        $ofertas = \App\Models\OfertaVaga::where('ciclo_id', $this->cicloAtivoId)->get();

        $ocupadas = InscricaoModel::selectRaw('curso_id, unidade_id, turno_id, count(*) as total')
            ->where('ciclo_id', $this->cicloAtivoId)
            ->whereHas('statusInscricao', function($q) {
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
            } elseif ($this->deseja_informar && !$this->temVagasDisponiveis) {
                // Se não tem vaga mas ele deseja informar, força ele a escolher.
                $regras['unidade_interesse'] = 'required';
                $regras['curso_interesse'] = 'required';
                $regras['turno_interesse'] = 'required';
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
        $regrasFinais = array_merge($this->rules(), $this->regrasPorEtapa($this->etapaAtual));
        
        $this->validate($regrasFinais, [
            'autorizacao_uso_infos.accepted' => 'Você precisa aceitar os termos.',
            'respostas.*.required' => 'Este campo é obrigatório.',
            'unidade_interesse.required' => 'Obrigatório caso deseje informar.',
            'curso_interesse.required' => 'Obrigatório caso deseje informar.',
            'turno_interesse.required' => 'Obrigatório caso deseje informar.'
        ]);

        if ($this->etapaAtual === 1 && $this->temVagasDisponiveis && $this->use_vacancy_limit) {
            $ofertasValidas = $this->getOfertasValidas();
            if ($ofertasValidas !== null && $this->unidade && $this->curso && $this->turno) {
                $key = "{$this->unidade}-{$this->curso}-{$this->turno}";
                if (!isset($ofertasValidas[$key])) {
                    $this->addError('curso', 'As vagas para esta opção acabaram de se esgotar! Por favor, escolha outro curso, turno ou unidade.');
                    $this->addError('turno', 'Vagas esgotadas.');
                    $this->atualizarDisponibilidade(); 
                    return; 
                }
            }
        }

        if ($this->etapaAtual === 1) {
            if (!$this->temVagasDisponiveis && $this->data_nascimento && $this->estado) {
                $this->salvarProgresso('Lead'); 
                $this->etapaAtual = 100; 
                
                $inscricaoDB = \App\Models\Inscricao::find($this->inscricaoId);
                if ($inscricaoDB) {
                    $inscricaoDB->update(['etapa_atual' => 100]);
                }
                
                $this->dispatch('inscricao-concluida'); 
                return;
            }
        }

        $this->salvarProgresso();

        if ($this->etapaAtual < $this->totalEtapas) {
            $this->etapaAtual++;
        } else {
            $inscricaoDB = \App\Models\Inscricao::find($this->inscricaoId);
            if ($inscricaoDB) {
                $inscricaoDB->update(['etapa_atual' => 99]);
                \App\Modules\Comunicacao\Services\AutomacaoService::disparar('inscricao.finalizada', $inscricaoDB);
            }

            $this->etapaAtual = 99; 
            
            $this->atualizarRankingInstantaneo();

            $this->dispatch('inscricao-concluida');
        }
    }

    private function atualizarRankingInstantaneo()
    {
        if (!$this->cicloAtivoId) return;

        \Illuminate\Support\Facades\DB::table('inscricoes')
            ->where('ciclo_id', $this->cicloAtivoId)
            ->where('etapa_atual', '!=', 99)
            ->whereNotNull('posicao_ranking_geral')
            ->update([
                'posicao_ranking' => null, 
                'posicao_ranking_geral' => null,
                'posicao_ranking_unidade' => null,
                'posicao_ranking_curso' => null,
            ]);

        $query = "
            WITH RankedData AS (
                SELECT id,
                    RANK() OVER (ORDER BY pontuacao_total DESC, created_at ASC) as rank_geral,
                    RANK() OVER (PARTITION BY unidade_id ORDER BY pontuacao_total DESC, created_at ASC) as rank_unidade,
                    RANK() OVER (PARTITION BY unidade_id, curso_id ORDER BY pontuacao_total DESC, created_at ASC) as rank_curso,
                    RANK() OVER (PARTITION BY unidade_id, curso_id, turno_id ORDER BY pontuacao_total DESC, created_at ASC) as rank_turma
                FROM inscricoes
                WHERE ciclo_id = :ciclo_id
                  AND deleted_at IS NULL
                  AND etapa_atual = 99
            )
            UPDATE inscricoes i
            SET posicao_ranking_geral = r.rank_geral,
                posicao_ranking_unidade = r.rank_unidade,
                posicao_ranking_curso = r.rank_curso,
                posicao_ranking = r.rank_turma
            FROM RankedData r
            WHERE i.id = r.id;
        ";

        \Illuminate\Support\Facades\DB::statement($query, ['ciclo_id' => $this->cicloAtivoId]);
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

            // SALVA OS DADOS DE INTERESSE
            'deseja_informar' => $this->deseja_informar ? 1 : 0,
            'unidade_interesse_id' => $this->unidade_interesse,
            'curso_interesse_id' => $this->curso_interesse,
            'turno_interesse_id' => $this->turno_interesse,
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

        $idade = \Carbon\Carbon::parse($this->data_nascimento)->age;
        $ofertasValidas = $this->getOfertasValidas();
        
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
            if (!in_array($oferta->curso->status, ['Ativo', 'ativo', '1', 1, true], true)) continue;
            if (!in_array($oferta->unidade->status, ['Ativa', 'ativa', '1', 1, true], true)) continue;
            
            $estadoUnidade = $oferta->unidade->estado ?: trim(explode('-', $oferta->unidade->nome)[0]);

            if (strtoupper($estadoUnidade) !== strtoupper($this->estado) && !$oferta->curso->permite_estado_diferente) {
                continue;
            }

            if ($this->use_vacancy_limit && $ofertasValidas !== null) {
                $key = "{$oferta->unidade_id}-{$oferta->curso_id}-{$oferta->turno_id}";
                if (!isset($ofertasValidas[$key])) continue;
            }

            $unidadesDisponiveisList->put($oferta->unidade_id, $oferta->unidade->nome);
        }

        if ($unidadesDisponiveisList->count() > 0) {
            $this->temVagasDisponiveis = true;
            $unidadesOrdenadas = $unidadesDisponiveisList->unique()->toArray();
            asort($unidadesOrdenadas);
            $this->unidadesDisponiveis = $unidadesOrdenadas;

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

        $idade = \Carbon\Carbon::parse($this->data_nascimento)->age;
        $ofertasValidas = $this->getOfertasValidas();
        $unidadeSelecionada = \App\Modules\Unidade\Domain\Models\Unidade::find($unidadeId);

        $estadoUnidadeSelecionada = $unidadeSelecionada->estado ?: trim(explode('-', $unidadeSelecionada->nome)[0]);

        $ofertasDaUnidade = \App\Models\OfertaVaga::with(['curso', 'turno'])
            ->where('ciclo_id', $this->cicloAtivoId)
            ->where('unidade_id', $unidadeId)
            ->where(function($q) use ($idade) {
                $q->whereNull('idade_min')->orWhere('idade_min', '<=', $idade);
            })
            ->where(function($q) use ($idade) {
                $q->whereNull('idade_max')->orWhere('idade_max', '>=', $idade);
            })
            ->get(); 

        foreach ($ofertasDaUnidade as $oferta) {
            if (!in_array($oferta->curso->status, ['Ativo', 'ativo', '1', 1, true], true)) continue;
            
            if ($unidadeSelecionada && strtoupper($estadoUnidadeSelecionada) !== strtoupper($this->estado) && !$oferta->curso->permite_estado_diferente) {
                continue;
            }

            if ($this->use_vacancy_limit && $ofertasValidas !== null) {
                $key = "{$unidadeId}-{$oferta->curso_id}-{$oferta->turno_id}";
                if (!isset($ofertasValidas[$key])) continue;
            }

            $this->cursosDisponiveis[$oferta->curso_id] = $oferta->curso->nome;
        }

        if (!empty($this->cursosDisponiveis)) {
            asort($this->cursosDisponiveis);
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

        $formatarCondicao = function($operador, $valor) {
            $valores = array_map('trim', explode(',', (string)$valor));
            switch ($operador) {
                case '=': return "Igual a '{$valor}'";
                case '!=': return "Diferente de '{$valor}'";
                case '>=': return "Maior ou igual a {$valor}";
                case '<=': return "Menor ou igual a {$valor}";
                case '>': return "Maior que {$valor}";
                case '<': return "Menor que {$valor}";
                case 'between': 
                    $v1 = $valores[0] ?? '';
                    $v2 = $valores[1] ?? '';
                    return "Entre {$v1} e {$v2}";
                case 'in': 
                    return "Dentre as opções (" . implode(' ou ', $valores) . ")";
                default: return "{$operador} {$valor}";
            }
        };

        $obterResposta = function($campo) {
            if ($campo === 'idade' && !empty($this->data_nascimento)) return \Carbon\Carbon::parse($this->data_nascimento)->age . ' anos';
            if ($campo === 'curso_id') return $this->cursosDisponiveis[$this->curso] ?? \App\Models\Curso::find($this->curso)->nome ?? 'Curso não informado';
            if ($campo === 'turno_id') return $this->turnosDisponiveis[$this->turno] ?? \App\Modules\Turno\Domain\Models\Turno::find($this->turno)->nome ?? 'Turno não informado';
            if ($campo === 'unidade_id') return $this->unidadesDisponiveis[$this->unidade] ?? \App\Modules\Unidade\Domain\Models\Unidade::find($this->unidade)->nome ?? 'Unidade não informada';
            if ($campo === 'estado') return $this->estado ?? 'Estado não informado';
            if ($campo === 'cidade') return $this->cidade ?? 'Cidade não informada';
            if ($campo === 'possui_deficiencia') return ucfirst($this->possui_deficiencia) ?? 'Não informada';
            
            if (isset($this->respostas[$campo])) {
                return is_array($this->respostas[$campo]) ? implode(', ', $this->respostas[$campo]) : $this->respostas[$campo];
            }
            return '';
        };

        $avaliarCondicao = function($regra) {
            if (($regra['escopo'] ?? 'especifico') === 'todos' && ($regra['tipo_regra'] ?? 'padrao') !== 'padrao') return true; 

            $campo = trim($regra['campo'] ?? '');
            $operador = trim($regra['operador'] ?? '=');
            $valorResposta = null;

            if ($campo === 'idade' && !empty($this->data_nascimento)) {
                $valorResposta = \Carbon\Carbon::parse($this->data_nascimento)->age;
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
                
                $valorEncontrado = $obterResposta($regra['campo'] ?? '');
                $respostaDada = (!empty($valorEncontrado) || $valorEncontrado === '0' || $valorEncontrado === 0) ? $valorEncontrado : 'Não informada / Em branco';

                $detalhes['auditoria_detalhada'][] = [
                    'tipo_regra' => 'padrao', 
                    'campo_avaliado' => $regra['campo'], 
                    'resposta_dada' => $respostaDada, 
                    'pontos_ganhos' => $pontos, 
                    'condicao' => $formatarCondicao($regra['operador'] ?? '=', $regra['valor'] ?? '')
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
                    $motivo = "Multiplicador de {$multiplicador}% aplicado sobre a Pontuação Base ({$scoreBase} pts).";
                }

                if ($pontosGanhos > 0) {
                    $scoreBonus += $pontosGanhos;
                    
                    $campoAvaliado = $escopo === 'todos' ? 'Regra Global' : ($regra['campo'] ?? 'Regra Específica');
                    
                    $valorEncontradoEspecial = $obterResposta($regra['campo'] ?? '');
                    $respostaDadaEspecial = $escopo === 'todos' ? 'Benefício aplicado a todos' : (!empty($valorEncontradoEspecial) ? $valorEncontradoEspecial : 'Ativada');

                    $detalhes['auditoria_detalhada'][] = [
                        'tipo_regra' => 'especial', 
                        'campo_avaliado' => $campoAvaliado, 
                        'resposta_dada' => $respostaDadaEspecial, 
                        'pontos_ganhos' => $pontosGanhos, 
                        'condicao' => $motivo
                    ];
                }
            }
        }

        $totalFinal = $scoreBase + $scoreBonus;
        if ($totalFinal > 0) {
            $detalhes['motivo_auditoria'] = "Avaliação de requisitos concluída com sucesso. A resposta do candidato correspondeu a {$acertosPadrao} regra(s) base. Pontos conquistados diretamente: {$scoreBase}. Acréscimos por bônus: {$scoreBonus}. Total = {$totalFinal} pontos.";
        } else {
            $detalhes = null; 
        }

        return ['total' => $totalFinal, 'detalhes' => $detalhes];
    }

    public function render()
    {
        // VARIÁVEIS DO NOVO BLOCO (ENVIADAS PARA A VIEW)
        $unidadesInteresseDb = collect();
        if ($this->estado) {
            $unidadesInteresseDb = \App\Modules\Unidade\Domain\Models\Unidade::where('estado', $this->estado)
                                    ->whereIn('status', ['Ativa', '1', true])
                                    ->get();
        }
        
        $cursosInteresseDb = \App\Models\Curso::whereIn('status', ['Ativo', 'ativo', '1', 1, true])->get();
        $turnosInteresseDb = \App\Modules\Turno\Domain\Models\Turno::all(); 

        return view('livewire.website.inscricao', [
            'unidadesInteresseDb' => $unidadesInteresseDb,
            'cursosInteresseDb' => $cursosInteresseDb,
            'turnosInteresseDb' => $turnosInteresseDb,
        ]);
    }
}