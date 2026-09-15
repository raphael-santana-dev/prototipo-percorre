<?php

namespace App\Traits;

trait WithToggleStatus
{
    public function toggleStatus($id)
    {
        if (!property_exists($this, 'modelClass')) {
            throw new \Exception('Você precisa definir a propriedade $modelClass no seu componente Livewire.');
        }

        $registro = $this->modelClass::findOrFail($id);
        
        $coluna = property_exists($this, 'statusColumn') ? $this->statusColumn : 'status';
        
        $unicoAtivo = property_exists($this, 'unicoAtivo') ? $this->unicoAtivo : false;

        $novoStatus = !$registro->{$coluna};

        if ($novoStatus && $unicoAtivo) {
            $this->modelClass::where('id', '!=', $id)->update([$coluna => false]);
        }

        $registro->update([$coluna => $novoStatus]);
        
        $this->dispatch('sucesso', msg: 'Status atualizado com sucesso!');
    }
}