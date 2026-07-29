<?php

namespace App\Traits;

use Illuminate\Support\Facades\Http;

trait WithCepConsulta
{
    // O Livewire aciona esta função automaticamente sempre que o "wire:model='cep'" é alterado
    public function updatedCep($valor)
    {
        $cepLimpo = preg_replace('/[^0-9]/', '', (string) $valor);

        if (strlen($cepLimpo) === 8) {
            $response = Http::get("https://viacep.com.br/ws/{$cepLimpo}/json/");

            if ($response->successful() && !isset($response['erro'])) {
                $this->logradouro = $response['logradouro'];
                $this->bairro = $response['bairro'];
                $this->cidade = $response['localidade'];
                $this->estado = $response['uf'];
                $this->dispararGatilhos();
            } else {
                $this->limparEndereco();
            }
        } else {
            $this->limparEndereco();
        }
    }

    private function limparEndereco()
    {
        $this->logradouro = null;
        $this->bairro = null;
        $this->cidade = null;
        $this->estado = null;
        $this->dispararGatilhos();
    }

    private function dispararGatilhos()
    {
        // Se o componente onde esta trait for usada tiver a função "atualizarDisponibilidade" (como é o caso da Inscrição), aciona ela.
        if (method_exists($this, 'atualizarDisponibilidade')) {
            $this->atualizarDisponibilidade();
        }
    }
}