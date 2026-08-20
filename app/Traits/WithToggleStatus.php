<?php

namespace App\Traits;

trait WithToggleStatus
{
    public function toggleStatus($id)
    {
        // 1. Verifica se a classe que está usando a Trait definiu qual é o Model
        if (!property_exists($this, 'modelClass')) {
            throw new \Exception('Você precisa definir a propriedade $modelClass no seu componente Livewire.');
        }

        // 2. Busca o registro de forma dinâmica
        $registro = $this->modelClass::findOrFail($id);
        
        // 3. Define qual a coluna no banco (padrão é 'status')
        $coluna = property_exists($this, 'statusColumn') ? $this->statusColumn : 'status';
        
        // 4. Regra opcional: "Apenas um pode ficar ativo" (útil para Ciclos)
        $unicoAtivo = property_exists($this, 'unicoAtivo') ? $this->unicoAtivo : false;

        $novoStatus = !$registro->{$coluna};

        // Aplica a regra de inativar os outros se necessário
        if ($novoStatus && $unicoAtivo) {
            $this->modelClass::where('id', '!=', $id)->update([$coluna => false]);
        }

        // Atualiza o registro alvo
        $registro->update([$coluna => $novoStatus]);
        
        $this->dispatch('sucesso', msg: 'Status atualizado com sucesso!');
    }
}