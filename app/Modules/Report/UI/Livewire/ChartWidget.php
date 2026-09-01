<?php

namespace App\Modules\Report\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Reactive;

class ChartWidget extends Component
{
    public string $chartId;
    
    // O atributo Reactive faz com que o gráfico se atualize sozinho se o filtro do pai mudar
    #[Reactive] 
    public array $config; 

    public function render()
    {
        return view('livewire.report.chart-widget');
    }
}