<?php

namespace App\Modules\Registration\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Crypt;
use App\Models\Inscricao;

#[Layout('components.layouts.public')] // Supondo que você tenha um layout de visitante sem a barra lateral
#[Title('Retomar Inscrição - Percorre')]
class RetomarInscricao extends Component
{
    public $token;
    public $cpf;
    public $data_nascimento;
    
    public ?Inscricao $inscricao = null;
    public bool $tokenInvalido = false;

    public function mount($token)
    {
        $this->token = $token;
        
        try {
            // Descriptografa o hash AES-256 gerado pelo botão da listagem
            $id = Crypt::decrypt($token);
            $this->inscricao = Inscricao::with('ciclo')->findOrFail($id);
            
        } catch (\Exception $e) {
            // Se o link foi adulterado ou é inválido
            $this->tokenInvalido = true;
        }
    }

    public function validar()
    {
        $this->validate([
            'cpf' => 'required|string',
            'data_nascimento' => 'required|date'
        ], [
            'cpf.required' => 'O CPF é obrigatório.',
            'data_nascimento.required' => 'A data de nascimento é obrigatória.'
        ]);

        // Sanitização para evitar falsos negativos por causa de máscaras (pontos e traços)
        $cpfLimpo = preg_replace('/[^0-9]/', '', $this->cpf);
        $cpfBanco = preg_replace('/[^0-9]/', '', $this->inscricao->cpf);

        // Formata a data do banco (Carbon) para 'Y-m-d' padrão do input type="date"
        $dataBanco = $this->inscricao->data_nascimento ? $this->inscricao->data_nascimento->format('Y-m-d') : null;

        if ($cpfLimpo === $cpfBanco && $this->data_nascimento === $dataBanco) {
            
            // Sucesso: Autoriza a sessão e salva o ID da inscrição para a próxima tela
            session()->put('inscricao_retomada_id', $this->inscricao->id);
            
            // Ajuste a rota abaixo para o nome real da sua rota do formulário público
            return redirect()->to('/inscricao');
        }

        // Se falhou, exibe erro genérico sem especificar qual campo errou por segurança
        $this->addError('cpf', 'As credenciais informadas não conferem com o titular desta inscrição.');
    }

    public function render()
    {
        return view('livewire.registration.retomar-inscricao');
    }
}