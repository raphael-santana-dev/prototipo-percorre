<?php

namespace App\Modules\Registration\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Crypt;
use App\Models\Inscricao;

#[Layout('components.layouts.public')] 
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
            $id = Crypt::decrypt($token);
            $this->inscricao = Inscricao::with('ciclo')->findOrFail($id);
            
        } catch (\Exception $e) {
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

        $cpfLimpo = preg_replace('/[^0-9]/', '', $this->cpf);
        $cpfBanco = preg_replace('/[^0-9]/', '', $this->inscricao->cpf);

        $dataBanco = $this->inscricao->data_nascimento ? $this->inscricao->data_nascimento->format('Y-m-d') : null;

        if ($cpfLimpo === $cpfBanco && $this->data_nascimento === $dataBanco) {
            
            session()->put('inscricao_retomada_id', $this->inscricao->id);
            
            return redirect()->to('/inscricao');
        }

        $this->addError('cpf', 'As credenciais informadas não conferem com o titular desta inscrição.');
    }

    public function render()
    {
        return view('livewire.registration.retomar-inscricao');
    }
}