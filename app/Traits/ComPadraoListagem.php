<?php

namespace App\Traits;

trait ComPadraoListagem
{
    public $porPagina = 10; 
    public $ordenacaoCampo = null; 
    public $ordenacaoDirecao = 'asc'; 

    public bool $permiteGrid = false; 
    public string $modoExibicao = 'lista';

    public function ordenarPor($campo)
    {
        if ($this->ordenacaoCampo === $campo) {
            $this->ordenacaoDirecao = $this->ordenacaoDirecao === 'asc' ? 'desc' : 'asc';
        } else {
            $this->ordenacaoCampo = $campo;
            $this->ordenacaoDirecao = 'asc';
        }
    }

    public function updatingPorPagina()
    {
        $this->resetPage();
    }

    public function alternarModoExibicao($modo)
    {
        if (in_array($modo, ['lista', 'grid'])) {
            $this->modoExibicao = $modo;
        }
    }
}