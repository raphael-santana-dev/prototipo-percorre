<?php

namespace App\Modules\Registration\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Inscricao;
use App\Models\StatusInscricao;
use App\Modules\Student\Domain\Models\Student;

#[Layout('components.layouts.app')]
#[Title('Detalhes da Inscrição')]
class RegistrationDetails extends Component
{
    public Inscricao $inscricao;
    public $status_selecionado; 

    public function mount($id)
    {
        // Carrega a inscrição e seus relacionamentos
        $this->inscricao = Inscricao::with(['unidade', 'curso', 'turno', 'ciclo'])->findOrFail($id);
        
        $this->status_selecionado = $this->inscricao->status_inscricao_id;
    }

    public function atualizarStatus()
    {
        // Garante que o usuário tem permissão para alterar status
        abort_if(!auth()->user()->hasRole('dev|admin'), 403, 'Você não tem permissão para alterar o status.');

        $statusNovo = StatusInscricao::find($this->status_selecionado);
        if (!$statusNovo) return;

        // REGRA DE NEGÓCIO: Criação do Estudante na Aprovação
        if (strtolower($statusNovo->nome) === 'aprovado' && !$this->inscricao->student_id) {
            
            // Busca ou Cria o estudante mapeando estritamente para as colunas da sua Migration
            $estudante = \App\Modules\Student\Domain\Models\Student::firstOrCreate(
                ['email' => $this->inscricao->email], // Chave de busca segura
                [
                    'name' => $this->inscricao->nome, // MAPEADO: 'nome' da Inscrição para 'name' do Student
                    'password' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(12)), // Senha obrigatória gerada aleatoriamente
                    'is_active' => true,
                ]
            );

            // Vincula o Estudante criado à inscrição
            $this->inscricao->student_id = $estudante->id;
        }

        // Atualiza e salva a inscrição
        $this->inscricao->status_inscricao_id = $statusNovo->id;
        $this->inscricao->save();

        $this->inscricao->refresh(); 
        session()->flash('sucesso', 'Status atualizado com sucesso! ' . ($this->inscricao->student_id && strtolower($statusNovo->nome) === 'aprovado' ? 'Cadastro de Estudante gerado e vinculado.' : ''));
    }

    public function render()
    {
        // Traz todos os status para montar o Select do painel
        $todosStatus = StatusInscricao::orderBy('nome')->get();

        return view('livewire.registration.registration-details', [
            'todosStatus' => $todosStatus
        ]);
    }
}