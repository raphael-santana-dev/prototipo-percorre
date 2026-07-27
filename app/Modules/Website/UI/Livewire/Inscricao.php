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

    public $camposDinamicos = [];

    public function mount()
    {

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