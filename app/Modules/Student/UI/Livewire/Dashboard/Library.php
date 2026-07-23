<?php

namespace App\Modules\Student\UI\Livewire\Dashboard;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('components.layouts.student-app')]
#[Title('Biblioteca - Portal do Aluno')]
class Library extends Component
{
    public function render()
    {
        // Uma view inline simples apenas para o nosso teste
        return <<<'HTML'
        <div>
            <h1 class="text-3xl font-bold text-slate-900">Biblioteca Virtual</h1>
            <p class="mt-2 text-slate-600">Acesse e-books, artigos e materiais de apoio.</p>
            <div class="mt-6 p-6 bg-white border border-slate-200 rounded-xl">
                <p>O conteúdo da biblioteca está disponível porque a feature está ATIVA.</p>
            </div>
        </div>
        HTML;
    }
}