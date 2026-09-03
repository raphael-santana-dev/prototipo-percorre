<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Ciclo2026Seeder extends Seeder
{
    public function run(): void
    {
        // 1. Cria o Ciclo Base
        $nomeCiclo = '2º Semestre 2026';
        $cicloId = DB::table('ciclos')->insertGetId([
            'nome' => $nomeCiclo,
            'ano' => 2026,
            'semestre' => 2,
            'data_inicio' => '2026-07-01 00:00:00',
            'data_fim' => '2026-12-31 23:59:59',
            'status' => true,
            'slug' => Str::slug($nomeCiclo) . '-' . time(),
            'regras_pontuacao' => json_encode([
                ['tipo_regra' => 'padrao', 'escopo' => 'especifico', 'campo' => 'voce_foi_indicado_por_algum_servico_de_assistencia_social_eou_saude', 'operador' => '=', 'valor' => 'Sim', 'pontos' => 30],
                ['tipo_regra' => 'padrao', 'escopo' => 'especifico', 'campo' => 'voce_foi_indicado_por_algum_servico_de_assistencia_social_eou_saude', 'operador' => '=', 'valor' => 'Não', 'pontos' => 15],
                ['tipo_regra' => 'padrao', 'escopo' => 'especifico', 'campo' => 'idade', 'operador' => 'between', 'valor' => '15,29', 'pontos' => 30],
                ['tipo_regra' => 'padrao', 'escopo' => 'especifico', 'campo' => 'renda_familiar', 'operador' => '<=', 'valor' => '1621', 'pontos' => 20],
                ['tipo_regra' => 'padrao', 'escopo' => 'especifico', 'campo' => 'renda_familiar', 'operador' => 'between', 'valor' => '1622,3242', 'pontos' => 15],
                ['tipo_regra' => 'padrao', 'escopo' => 'especifico', 'campo' => 'renda_familiar', 'operador' => 'between', 'valor' => '3243,4863', 'pontos' => 10],
                ['tipo_regra' => 'padrao', 'escopo' => 'especifico', 'campo' => 'renda_familiar', 'operador' => 'between', 'valor' => '4864,6484', 'pontos' => 5],
                ['tipo_regra' => 'padrao', 'escopo' => 'especifico', 'campo' => 'renda_familiar', 'operador' => 'between', 'valor' => '6485,8105', 'pontos' => 2],
                ['tipo_regra' => 'padrao', 'escopo' => 'especifico', 'campo' => 'quanto_tempo_de_deslocamento_voce_leva_da_sua_casa_ate_a_unidade_escolhida', 'operador' => '=', 'valor' => 'Até 30 minutos', 'pontos' => 20],
                ['tipo_regra' => 'padrao', 'escopo' => 'especifico', 'campo' => 'quanto_tempo_de_deslocamento_voce_leva_da_sua_casa_ate_a_unidade_escolhida', 'operador' => '=', 'valor' => 'Entre 30 minutos e 1 hora', 'pontos' => 15],
                ['tipo_regra' => 'padrao', 'escopo' => 'especifico', 'campo' => 'quanto_tempo_de_deslocamento_voce_leva_da_sua_casa_ate_a_unidade_escolhida', 'operador' => 'in', 'valor' => 'Entre 1 hora e 1 hora e 30 minutos,Acima de 1 hora e 30 minutos', 'pontos' => 5],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Vincula o Pipeline de Status (Kanban)
        $statusDisponiveis = DB::table('status_inscricoes')->orderBy('id')->get();
        foreach ($statusDisponiveis as $index => $status) {
            DB::table('ciclo_status_inscricao')->insertOrIgnore([
                'ciclo_id' => $cicloId,
                'status_inscricao_id' => $status->id,
                'ordem' => $index + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 3. Cadastra os Turnos (Separando Manhã e Tarde)
        $turnos = [
            ['nome' => 'Manhã', 'inicio' => '08:00:00', 'fim' => '12:00:00'],
            ['nome' => 'Tarde', 'inicio' => '13:00:00', 'fim' => '17:00:00'],
            ['nome' => 'Noite', 'inicio' => '18:00:00', 'fim' => '22:00:00'],
            ['nome' => 'Sábado', 'inicio' => '08:00:00', 'fim' => '12:00:00'],
        ];

        foreach ($turnos as $turno) {
            DB::table('turnos')->insertOrIgnore([
                'nome' => $turno['nome'],
                'slug' => Str::slug($turno['nome']),
                'horario_inicio' => $turno['inicio'],
                'horario_fim' => $turno['fim'],
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 4. Inserção das Ofertas (Vagas Manhã/Tarde divididas + Nomes de Unidades Refatorados UF-Cidade)
        $this->inserirOferta($cicloId, 'gestao_empresarial_erp', 'mg_barreiro', 'MG-Barreiro', 'Manhã', 50, 15, 17);
        $this->inserirOferta($cicloId, 'gestao_empresarial_erp', 'mg_barreiro', 'MG-Barreiro', 'Tarde', 50, 15, 17);
        
        $this->inserirOferta($cicloId, 'suporte_ti', 'sp_hortolandia', 'SP-Hortolândia', 'Manhã', 20, 15, 29);
        $this->inserirOferta($cicloId, 'suporte_ti', 'sp_hortolandia', 'SP-Hortolândia', 'Tarde', 20, 15, 29);
        
        $this->inserirOferta($cicloId, 'gestao_empresarial_erp', 'sp_itaquera', 'SP-Itaquera', 'Manhã', 40, 15, 29);
        $this->inserirOferta($cicloId, 'gestao_empresarial_erp', 'sp_itaquera', 'SP-Itaquera', 'Tarde', 40, 15, 29);
        
        $this->inserirOferta($cicloId, 'gestao_empresarial_erp', 'sp_jardim_angela', 'SP-Jardim Angela', 'Manhã', 40, 15, 17);
        $this->inserirOferta($cicloId, 'gestao_empresarial_erp', 'sp_jardim_angela', 'SP-Jardim Angela', 'Tarde', 40, 15, 17);
        
        $this->inserirOferta($cicloId, 'programacao_web', 'rs_porto_alegre', 'RS-Porto Alegre', 'Manhã', 30, 15, 29);
        $this->inserirOferta($cicloId, 'programacao_web', 'rs_porto_alegre', 'RS-Porto Alegre', 'Tarde', 30, 15, 29);
        
        $this->inserirOferta($cicloId, 'gestao_empresarial_erp', 'pe_recife', 'PE-Recife', 'Manhã', 40, 15, 29);
        $this->inserirOferta($cicloId, 'gestao_empresarial_erp', 'pe_recife', 'PE-Recife', 'Tarde', 40, 15, 29);
        
        $this->inserirOferta($cicloId, 'gestao_empresarial_erp', 'mg_sao_gabriel', 'MG-São Gabriel', 'Manhã', 50, 15, 29);
        $this->inserirOferta($cicloId, 'gestao_empresarial_erp', 'mg_sao_gabriel', 'MG-São Gabriel', 'Tarde', 50, 15, 29);
        
        $this->inserirOferta($cicloId, 'gestao_empresarial_erp', 'sp_santana', 'SP-Santana', 'Manhã', 25, 15, 17);
        $this->inserirOferta($cicloId, 'gestao_empresarial_erp', 'sp_santana', 'SP-Santana', 'Tarde', 25, 15, 17);
        $this->inserirOferta($cicloId, 'programacao_web', 'sp_santana', 'SP-Santana', 'Noite', 100, 15, 29);
        $this->inserirOferta($cicloId, 'programacao_web', 'sp_santana', 'SP-Santana', 'Manhã', 25, 15, 29);
        $this->inserirOferta($cicloId, 'programacao_web', 'sp_santana', 'SP-Santana', 'Tarde', 25, 15, 29);
        $this->inserirOferta($cicloId, 'suporte_ti', 'sp_santana', 'SP-Santana', 'Manhã', 40, 15, 29);
        $this->inserirOferta($cicloId, 'suporte_ti', 'sp_santana', 'SP-Santana', 'Tarde', 40, 15, 29);
        $this->inserirOferta($cicloId, 'ia', 'sp_santana', 'SP-Santana', 'Noite', 56, 18, 29);
        $this->inserirOferta($cicloId, 'ia', 'sp_santana', 'SP-Santana', 'Manhã', 28, 18, 29);
        $this->inserirOferta($cicloId, 'ia', 'sp_santana', 'SP-Santana', 'Tarde', 28, 18, 29);
        $this->inserirOferta($cicloId, 'zendesk', 'sp_santana', 'SP-Santana', 'Tarde', 50, 15, 29);
        $this->inserirOferta($cicloId, 'power_bi', 'sp_santana', 'SP-Santana', 'Sábado', 25, 15, 29);
        $this->inserirOferta($cicloId, 'ciberseguranca', 'sp_santana', 'SP-Santana', 'Sábado', 50, 15, 29);
        $this->inserirOferta($cicloId, 'digital_commerce_shopify', 'sp_santana', 'SP-Santana', 'Sábado', 50, 15, 29);
        $this->inserirOferta($cicloId, 'protheus_instalacao_config', 'sp_santana', 'SP-Santana', 'Noite', 25, 18, 29);
        
        $this->inserirOferta($cicloId, 'gestao_empresarial_erp', 'rj_rio_de_janeiro', 'RJ-Rio de Janeiro', 'Tarde', 50, 15, 22);
        
        $this->inserirOferta($cicloId, 'marketing_digital', 'sp_rio_preto', 'SP-Rio Preto', 'Manhã', 25, 15, 17);
        $this->inserirOferta($cicloId, 'marketing_digital', 'sp_rio_preto', 'SP-Rio Preto', 'Tarde', 25, 15, 17);
        
        $this->inserirOferta($cicloId, 'gestao_empresarial_erp', 'sp_santos', 'SP-Santos', 'Tarde', 40, 15, 17);
        
        $this->inserirOferta($cicloId, 'programacao_web', 'sp_carapicuiba', 'SP-Carapicuíba', 'Manhã', 25, 15, 17);
        $this->inserirOferta($cicloId, 'programacao_web', 'sp_carapicuiba', 'SP-Carapicuíba', 'Tarde', 25, 15, 17);
    }

    private function inserirOferta($cicloId, $cursoSlug, $unidadeSlug, $unidadeNome, $turnoNome, $vagas, $idadeMin = null, $idadeMax = null)
    {
        $cursoId = DB::table('cursos')->where('slug', $cursoSlug)->value('id');
        $turnoId = DB::table('turnos')->where('nome', $turnoNome)->value('id');
        
        $unidadeId = DB::table('unidades')->where('slug', $unidadeSlug)->value('id');
        
        // Se a unidade não existir no banco, cria na hora
        if (!$unidadeId) {
            $unidadeId = DB::table('unidades')->insertGetId([
                'nome' => $unidadeNome,
                'slug' => $unidadeSlug,
                'status' => 'Ativa',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if ($cursoId && $unidadeId && $turnoId) {
            // Relacionamento de Ofertas de Vagas com limite de idades e vagas
            DB::table('ofertas_vagas')->insertOrIgnore([
                'ciclo_id' => $cicloId,
                'curso_id' => $cursoId,
                'unidade_id' => $unidadeId,
                'turno_id' => $turnoId,
                'vagas' => $vagas,
                'idade_min' => $idadeMin,
                'idade_max' => $idadeMax,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Preenchimento de todas as tabelas pivô do planejamento
            DB::table('ciclo_unidade')->insertOrIgnore(['ciclo_id' => $cicloId, 'unidade_id' => $unidadeId]);
            DB::table('ciclo_turno')->insertOrIgnore(['ciclo_id' => $cicloId, 'turno_id' => $turnoId]);
            DB::table('ciclo_curso')->insertOrIgnore(['ciclo_id' => $cicloId, 'curso_id' => $cursoId]);
            DB::table('curso_unidade')->insertOrIgnore(['curso_id' => $cursoId, 'unidade_id' => $unidadeId]);
            DB::table('curso_turno')->insertOrIgnore(['curso_id' => $cursoId, 'turno_id' => $turnoId]);
        }
    }
}