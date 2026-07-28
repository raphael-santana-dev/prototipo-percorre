<?php

namespace App\Traits;

trait ComPadraoListagem
{
    public $porPagina = 10; // Valor padrão do seletor
    public $ordenacaoCampo = null; // Qual coluna está a ordenar
    public $ordenacaoDirecao = 'asc'; // asc ou desc

    // Método disparado ao clicar no título de uma coluna
    public function ordenarPor($campo)
    {
        if ($this->ordenacaoCampo === $campo) {
            // Se clicar na mesma coluna, inverte a direção
            $this->ordenacaoDirecao = $this->ordenacaoDirecao === 'asc' ? 'desc' : 'asc';
        } else {
            // Se clicar numa coluna nova, começa de forma ascendente
            $this->ordenacaoCampo = $campo;
            $this->ordenacaoDirecao = 'asc';
        }
    }

    // Se o utilizador mudar a quantidade de registos por página, voltamos à página 1
    public function updatingPorPagina()
    {
        $this->resetPage();
    }
}