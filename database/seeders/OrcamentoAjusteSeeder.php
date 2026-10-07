<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Financeiro\Domain\Models\Orcamento;
use App\Modules\Financeiro\Domain\Models\CentroCusto;

class OrcamentoAjusteSeeder extends Seeder
{
    public function run(): void
    {
        $centrosCusto = CentroCusto::pluck('codigo')->toArray();
        
        if (empty($centrosCusto)) {
            $this->command->error('Execute o CentroCustoSeeder primeiro!');
            return;
        }

        // 1. Procura orçamentos onde o Centro de Custo está vazio ou com espaços
        $orcamentosVazios = Orcamento::with('itens')->where('ccusto', '')->orWhere('ccusto', 'like', '% %')->get();
        $itensMovidos = 0;

        foreach ($orcamentosVazios as $orcamentoVazio) {
            
            // 2. Percorre cada Natureza (Item) desse orçamento gigante
            foreach ($orcamentoVazio->itens as $item) {
                
                // Escolhe um Centro de Custo aleatório da nossa base (ex: 001, 002)
                $ccustoSorteado = $centrosCusto[array_rand($centrosCusto)];

                // Cria a nova chave do Cabeçalho (agora sem a natureza)
                $chaveComposta = "{$orcamentoVazio->filial}_{$orcamentoVazio->ano}_{$ccustoSorteado}";

                // 3. Verifica se já existe um orçamento para este C.Custo, se não, cria
                $novoOrcamento = Orcamento::firstOrCreate(
                    ['chave_composta' => $chaveComposta],
                    [
                        'filial' => $orcamentoVazio->filial,
                        'ano'    => $orcamentoVazio->ano,
                        'ccusto' => $ccustoSorteado,
                        'moeda'  => $orcamentoVazio->moeda,
                        'cmoeda' => $orcamentoVazio->cmoeda,
                        'xcat'   => $orcamentoVazio->xcat,
                        'status' => 'Criado',
                    ]
                );

                // 4. Move o item (Natureza) para o novo orçamento
                $item->update(['orcamento_id' => $novoOrcamento->id]);
                $itensMovidos++;
            }

            // 5. Apaga o orçamento original vazio se ele ficou sem itens
            if ($orcamentoVazio->itens()->count() === 0) {
                $orcamentoVazio->delete();
            }
        }

        $this->command->info("{$itensMovidos} naturezas (linhas) foram distribuídas pelos Centros de Custo reais.");
    }
}