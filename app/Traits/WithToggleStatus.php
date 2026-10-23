<?php

namespace App\Traits;

use App\Models\Ciclo;
use App\Models\Formulario;

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

        // REGRA 1 e 2: Ciclos de Inscrição (Apenas 1 'regular' ativo, mas N 'customizado' ativos)
        if ($registro instanceof Ciclo || $this->modelClass === Ciclo::class) {
            $tipoCiclo = $registro->tipo_ciclo ?? 'regular';
            if ($novoStatus && $tipoCiclo === 'regular') {
                Ciclo::where('tipo_ciclo', 'regular')
                    ->where('id', '!=', $registro->id)
                    ->update([$coluna => false]);
            }
            $registro->update([$coluna => $novoStatus]);
            $this->dispatch('sucesso', msg: 'Status do ciclo atualizado com sucesso!');
            return;
        }

        // REGRA 3: Formulários (Podem haver N formulários de aprendizagem, pré-inscrição ou gerais ativos)
        if ($registro instanceof Formulario || $this->modelClass === Formulario::class) {
            $dadosUpdate = [$coluna => $novoStatus];
            
            // Removemos o espaço extra e usamos a classe corretamente
            if ($novoStatus && \Illuminate\Support\Facades\Schema::hasColumn('formularios', 'is_rascunho')) {
                $dadosUpdate['is_rascunho'] = false;
            }
            
            $registro->update($dadosUpdate);
            $this->dispatch('sucesso', msg: 'Status do formulário atualizado com sucesso!');
            return;
        }

        // Comportamento padrão para outras models do sistema
        if ($novoStatus && $unicoAtivo) {
            $this->modelClass::where('id', '!=', $id)->update([$coluna => false]);
        }

        $registro->update([$coluna => $novoStatus]);
        
        $this->dispatch('sucesso', msg: 'Status atualizado com sucesso!');
    }
}