<?php

namespace App\Modules\Period\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use App\Models\Ciclo;

#[Layout('components.layouts.app')]
#[Title('Detalhes do Ciclo - Administrativo')]
class PeriodDetails extends Component
{
    use WithPagination;

    public Ciclo $ciclo;
    
    // Campo para buscar inscrições específicas
    public string $search = '';

    public function mount(int $id)
    {
        abort_if(!auth()->user()->hasRole('dev|admin'), 403);
        
        // Eager load apenas dos cursos, as inscrições deixamos para o render (para paginar)
        $this->ciclo = Ciclo::with('cursos')->findOrFail($id);
    }

    // Reseta a paginação ao digitar na busca
    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        // Busca as inscrições deste ciclo com filtros
        $inscricoes = $this->ciclo->inscricoes()
            ->when($this->search, function ($query) {
                $query->where(function($q) {
                    $q->where('nome', 'ilike', '%' . $this->search . '%') // ilike é ótimo para PostgreSQL
                      ->orWhere('cpf', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'ilike', '%' . $this->search . '%');
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('livewire.period.period-details', [
            'inscricoes' => $inscricoes
        ]);
    }
}