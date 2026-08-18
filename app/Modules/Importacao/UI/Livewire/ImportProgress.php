<?php

namespace App\Modules\Importacao\UI\Livewire;

use Livewire\Component;
use App\Models\Importacao;

class ImportProgress extends Component
{
    public function render()
    {
        // Busca apenas as importações do usuário logado que não terminaram
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