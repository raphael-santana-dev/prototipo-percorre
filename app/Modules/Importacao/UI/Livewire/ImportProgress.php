<?php

namespace App\Modules\Importacao\UI\Livewire;

use Livewire\Component;
use App\Models\Importacao;

class ImportProgress extends Component
{
    public function render()
    {
        if (!feature('importacao.acessar') || (!auth()->user()->hasRole('dev') && !auth()->user()->can('importacao.acessar'))) {
            return <<<'HTML'
            <div></div>
            HTML;
        }
        $ativas = Importacao::where('user_id', auth()->id())
            ->whereIn('status', ['mapeamento', 'na_fila', 'processando'])
            ->orderBy('id', 'desc')
            ->get();

        return view('livewire.importacao.import-progress', [
            'ativas' => $ativas,
            'totalAtivas' => $ativas->count()
        ]);
    }
}