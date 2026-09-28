<?php

namespace App\Modules\SystemTasks\UI\Livewire;

use Livewire\Component;
use App\Models\SystemTask;

class SystemTaskProgress extends Component
{
    public function render()
    {
        if (!feature('tarefas.acessar') || (!auth()->user()->hasRole('dev') && !auth()->user()->can('tarefas.acessar'))) {
            return <<<'HTML'
            <div></div>
            HTML;
        }
        $ativas = SystemTask::where('user_id', auth()->id())
            ->whereIn('status', ['mapeamento', 'na_fila', 'processando'])
            ->orderBy('id', 'desc')
            ->get();

        return view('livewire.system-tasks.system-task-progress', [
            'ativas' => $ativas,
            'totalAtivas' => $ativas->count()
        ]);
    }
}