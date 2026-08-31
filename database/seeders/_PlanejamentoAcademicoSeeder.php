<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlanejamentoAcademicoSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Cria o Ciclo respeitando a migration de ciclos
        $cicloId = DB::table('ciclos')->insertGetId([
            'nome' => '2º Semestre 2026',
            'ano' => 2026,
            'semestre' => 2,
            'data_inicio' => '2026-07-01 00:00:00',
            'data_fim' => '2026-12-31 23:59:59',
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Cadastra os Turnos exigidos pela migration (com horários e status)
        $turnos = [
            ['nome' => 'Manhã/Tarde', 'inicio' => '08:00:00', 'fim' => '17:00:00'],
            ['nome' => 'Tarde', 'inicio' => '13:00:00', 'fim' => '17:00:00'],
            ['nome' => 'Noite', 'inicio' => '18:00:00', 'fim' => '22:00:00'],
            ['nome' => 'Sábado', 'inicio' => '08:00:00', 'fim' => '12:00:00'],
        ];

        foreach ($turnos as $turno) {
            DB::table('turnos')->insertOrIgnore([
                'nome' => $turno['nome'],
                'horario_inicio' => $turno['inicio'],
                'horario_fim' => $turno['fim'],
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 3. Cadastra as Ofertas com TODOS os mapeamentos relacionais e nomes reais
        $this->inserirOferta($cicloId, 'gestao_empresarial_erp', 'barreiro_bh_mg', 'PUC - Barreiro', 'Manhã/Tarde', 100);
        $this->inserirOferta($cicloId, 'gestao_empresarial_erp', 'sao_gabriel_bh_mg', 'São Gabriel', 'Manhã/Tarde', 100);
        $this->inserirOferta($cicloId, 'gestao_empresarial_erp', 'boa_vista_recife_pe', 'UNICAP - Recife', 'Manhã/Tarde', 80);
        $this->inserirOferta($cicloId, 'gestao_empresarial_erp', 'engenho_novo_rj', 'Celso Lisboa - Rio de Janeiro', 'Tarde', 50);
        $this->inserirOferta($cicloId, 'programacao_web', 'cidade_baixa_poa_rs', 'PUC-RS / FIJO - av. Ipiranga 6681', 'Manhã/Tarde', 60);
        $this->inserirOferta($cicloId, 'analise_dados_ia_sustentabilidade', 'sede_santana_sp', 'Sede Santana', 'Manhã/Tarde', 56);
        $this->inserirOferta($cicloId, 'analise_dados_ia_sustentabilidade', 'sede_santana_sp', 'Sede Santana', 'Noite', 25);
        $this->inserirOferta($cicloId, 'ciberseguranca', 'sede_santana_sp', 'Sede Santana', 'Sábado', 50);
        $this->inserirOferta($cicloId, 'digital_commerce_shopify', 'sede_santana_sp', 'Sede Santana', 'Sábado', 25);
        $this->inserirOferta($cicloId, 'gestao_empresarial_erp', 'sede_santana_sp', 'Sede Santana', 'Manhã/Tarde', 50);
        $this->inserirOferta($cicloId, 'gestao_empresarial_erp', 'vila_nova_santos_sp', 'Santos', 'Tarde', 40);
        $this->inserirOferta($cicloId, 'gestao_empresarial_erp', 'itaquera_sp', 'Dom Bosco - Itaquera', 'Manhã/Tarde', 80);
        $this->inserirOferta($cicloId, 'gestao_empresarial_erp', 'jardim_angela_sp', 'Santos Mártires - Jardim Angela', 'Manhã/Tarde', 80);
        $this->inserirOferta($cicloId, 'sustentabilidade_digital', 'sede_santana_sp', 'Sede Santana', 'Manhã/Tarde', 56);
        $this->inserirOferta($cicloId, 'ia', 'sede_santana_sp', 'Sede Santana', 'Sábado', 75);
        $this->inserirOferta($cicloId, 'marketing_digital', 'sao_jose_rio_preto_sp', 'R. Paschoal Decrescenzo, 599 · São José do Rio Preto - SP', 'Manhã/Tarde', 50);
        $this->inserirOferta($cicloId, 'power_bi', 'sede_santana_sp', 'Sede Santana', 'Sábado', 50);
        $this->inserirOferta($cicloId, 'programacao_web', 'sede_santana_sp', 'Sede Santana', 'Manhã/Tarde', 50);
        $this->inserirOferta($cicloId, 'programacao_web', 'cidade_ariston_carapicuiba_sp', 'Carapicuíba - Rua Flora Rica,  n° 11 - 1° andar - CEP 06395-330 - Cidade Ariston - Carapicuíba - SP.', 'Manhã/Tarde', 50);
        $this->inserirOferta($cicloId, 'programacao_web', 'sede_santana_sp', 'Sede Santana', 'Noite', 100);
        $this->inserirOferta($cicloId, 'protheus_instalacao_config', 'sede_santana_sp', 'Sede Santana', 'Noite', 25);
        $this->inserirOferta($cicloId, 'suporte_ti', 'jardim_boa_esperanca_hortolandia_sp', 'Núcleo Vinde a mim - Hortolândia', 'Manhã/Tarde', 40);
        $this->inserirOferta($cicloId, 'suporte_ti', 'sede_santana_sp', 'Sede Santana', 'Manhã/Tarde', 160);
        $this->inserirOferta($cicloId, 'zendesk', 'sede_santana_sp', 'Sede Santana', 'Manhã/Tarde', 50);
        $this->inserirOferta($cicloId, 'zendesk', 'santo_amaro_sp', 'Santo Amaro -  RUA ISABEL SCHMIDT, 349 SANTO AMARO. METRÔ ADOLFO PINHEIRO (UNISA)', 'Sábado', 50);
    }

    // Adicionamos os parâmetros $idadeMin e $idadeMax na assinatura
    private function inserirOferta($cicloId, $cursoSlug, $unidadeSlug, $unidadeNomePlanilha, $turnoNome, $vagas, $idadeMin = null, $idadeMax = null)
    {
        $cursoId = DB::table('cursos')->where('slug', $cursoSlug)->value('id');
        $turnoId = DB::table('turnos')->where('nome', $turnoNome)->value('id');
        
        $unidadeId = DB::table('unidades')->where('slug', $unidadeSlug)->value('id');
        
        if (!$unidadeId) {
            $unidadeId = DB::table('unidades')->insertGetId([
                'nome' => $unidadeNomePlanilha,
                'slug' => $unidadeSlug,
                'status' => 'Ativa',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if ($cursoId && $unidadeId && $turnoId) {
            DB::table('ofertas_vagas')->insertOrIgnore([
                'ciclo_id' => $cicloId,
                'curso_id' => $cursoId,
                'unidade_id' => $unidadeId,
                'turno_id' => $turnoId,
                'vagas' => $vagas,
                'idade_min' => $idadeMin, // <-- SALVANDO AQUI
                'idade_max' => $idadeMax, // <-- SALVANDO AQUI
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('ciclo_unidade')->insertOrIgnore(['ciclo_id' => $cicloId, 'unidade_id' => $unidadeId]);
            DB::table('ciclo_turno')->insertOrIgnore(['ciclo_id' => $cicloId, 'turno_id' => $turnoId]);
            DB::table('ciclo_curso')->insertOrIgnore(['ciclo_id' => $cicloId, 'curso_id' => $cursoId]);
            DB::table('curso_unidade')->insertOrIgnore(['curso_id' => $cursoId, 'unidade_id' => $unidadeId]);
            DB::table('curso_turno')->insertOrIgnore(['curso_id' => $cursoId, 'turno_id' => $turnoId]);
        }
    }
}