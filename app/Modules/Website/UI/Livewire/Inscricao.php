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

#[Layout('components.layouts.public')]
#[Title('Inscrição - Instituto Percorre')]
class Inscricao extends Component
{
    public int $etapaAtual = 1;
    public int $totalEtapas = 1;
    public $inscricaoId = null;
    public $cicloAtivoId = null;
    public $inscricoesAbertas = false;


    //Campos estáticos - Etapa 1
    public $nome, $nome_social, $cpf, $email, $celular, $data_nascimento, $cep, $logradouro, $bairro, $cidade, $estado, $numero, $complemento, $unidade, $turno, $curso, $natureza_deficiencia;
    public $possui_deficiencia = 'nao';
    public $possui_nome_social = 'nao';
    public $autorizacao_uso_infos = false;

    public $temVagasDisponiveis = true;

    public $camposDinamicos = [];
    public $respostas = [];

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
                    $this->respostas[$campo->name] = '';
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
            $senhaProvisoria = Str::random(8);
            $usuarioAluno = User::firstOrCreate(
                ['email' => $this->email],
                ['name' => $this->nome, 'password' => Hash::make($senhaProvisoria), 'precisa_trocar_senha' => true]
            );

            if ($usuarioAluno->wasRecentlyCreated) {
                $usuarioAluno->assignRole('aluno');
                \Illuminate\Support\Facades\Mail::to($usuarioAluno->email)
                    ->send(new \App\Mail\BoasVindasAluno($usuarioAluno, $senhaProvisoria));
            }
            Inscricao::where('id', $this->inscricaoId)->update(['usuario_id' => $usuarioAluno->id]);
            
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

        if ($this->etapaAtual === $this->totalEtapas && !$statusForcado) {
            $nomeStatus = 'Pendente';
            
            $pontuacao = $this->calcularPontuacao();
            $dados['pontuacao_total'] = $pontuacao['total'];
            $dados['pontuacao_detalhes'] = $pontuacao['detalhes'];
            
        } elseif ($statusForcado) {
            $nomeStatus = ucfirst($statusForcado); 
        }

        $statusDb = StatusInscricao::where('nome', $nomeStatus)->first();
        $dados['status_inscricao_id'] = $statusDb ? $statusDb->id : 1;

        $inscricao = Inscricao::updateOrCreate(['id' => $this->inscricaoId], $dados);
        if (!$this->inscricaoId) $this->inscricaoId = $inscricao->id;
    }

    public function restaurarDeDadosSalvos()
    {
        if ($this->estado && $this->data_nascimento) {
            $this->atualizarDisponibilidade();
            
            // Re-aplicando as seleções que vieram do Cache
            // if ($this->unidade) $this->updatedUnidade($this->unidade);
            // if ($this->curso) $this->updatedCurso($this->curso);
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
                // Rule::unique('inscricoes', 'cpf')->ignore($this->inscricaoId) // Corrigido de 'inscricaos' para 'inscricoes'
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

    public function updatedCep($valor)
    {
        $cepLimpo = preg_replace('/[^0-9]/', '', $valor);

        if (strlen($cepLimpo) === 8) {
            $response = Http::get("https://viacep.com.br/ws/{$cepLimpo}/json/");

            if ($response->successful() && !isset($response['erro'])) {
                $this->logradouro = $response['logradouro'];
                $this->bairro = $response['bairro'];
                $this->cidade = $response['localidade'];
                $this->estado = $response['uf'];
                // $this->atualizarDisponibilidade();
            } else {
                $this->limparEndereco();
            }
        } else {
            $this->limparEndereco();
        }
    }

    private function limparEndereco()
    {
        $this->logradouro = null;
        $this->bairro = null;
        $this->cidade = null;
        $this->estado = null;
        // $this->atualizarDisponibilidade();
    }

    public function render()
    {
        return view('livewire.website.inscricao');
    }
}