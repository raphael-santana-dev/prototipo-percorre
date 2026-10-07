<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Financeiro\Domain\Models\Orcamento;
use App\Modules\Financeiro\Domain\Models\CentroCusto;

class OrcamentoAjusteSeeder extends Seeder
{
    public function run(): void
    {
        $codigosCC = CentroCusto::pluck('codigo')->toArray();
        
        if (empty($codigosCC)) {
            $this->command->error('Execute o CentroCustoSeeder primeiro!');
            return;
        }

        $orcamentos = Orcamento::all();
        $atualizados = 0;

        foreach ($orcamentos as $orc) {
            $naturezaLimpa = trim($orc->natureza);
            
            // Se o centro de custo for vazio ou apenas espaços, sorteia um dos criados
            $ccustoValido = empty(trim($orc->ccusto)) ? $codigosCC[array_rand($codigosCC)] : trim($orc->ccusto);

            // Refaz a chave composta para não bugar a importação futura
            $novaChave = "{$orc->filial}_{$orc->ano}_{$naturezaLimpa}_{$ccustoValido}";

            $orc->update([
                'natureza' => $naturezaLimpa,
                'ccusto' => $ccustoValido,
                'chave_composta' => $novaChave
            ]);

            $atualizados++;
        }

        $this->command->info("{$atualizados} orçamentos foram limpos e vinculados a Centros de Custo aleatórios.");
    }
}