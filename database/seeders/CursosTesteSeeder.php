<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Curso;

class CursosTesteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cursos = [
            [
                'nome' => 'Gestão Empresarial com ERP',
                'slug' => 'gestao_empresarial_erp',
                'status' => 'Ativo',
                'turnos' => ['manha', 'tarde', 'noite'],
                'permite_estado_diferente' => false,
            ],

            [
                'nome' => 'Programação Web',
                'slug' => 'programacao_web',
                'status' => 'Ativo',
                'turnos' => ['manha', 'tarde', 'noite'],
                'permite_estado_diferente' => true,
            ],

            [
                'nome' => 'Power BI',
                'slug' => 'power_bi',
                'status' => 'Ativo',
                'turnos' => ['manha', 'tarde', 'noite'],
                'permite_estado_diferente' => true,
            ],

            [
                'nome' => 'Zendesk',
                'slug' => 'zendesk',
                'status' => 'Ativo',
                'turnos' => ['manha', 'tarde'],
                'permite_estado_diferente' => true,
            ],

            [
                'nome' => 'IA',
                'slug' => 'ia',
                'status' => 'Ativo',
                'turnos' => ['manha', 'tarde', 'noite'],
                'min_idade' => 16,
                'max_idade' => 35,
                'permite_estado_diferente' => true,
            ],

            [
                'nome' => 'Suporte TI',
                'slug' => 'suporte_ti',
                'status' => 'Ativo',
                'turnos' => ['manha', 'tarde'],
                'permite_estado_diferente' => true,
            ],

            [
                'nome' => 'Cibersegurança',
                'slug' => 'ciberseguranca',
                'status' => 'Ativo',
                'turnos' => ['tarde', 'noite'],
                'permite_estado_diferente' => true,
            ],

            [
                'nome' => 'Digital Commerce com Ênfase em Shopify',
                'slug' => 'digital_commerce_shopify',
                'status' => 'Ativo',
                'turnos' => ['manha', 'tarde'],
                'permite_estado_diferente' => true,
            ],

            [
                'nome' => 'Protheus: Instalação e Configuração',
                'slug' => 'protheus_instalacao_config',
                'status' => 'Ativo',
                'turnos' => ['tarde', 'noite'],
                'permite_estado_diferente' => true,
            ],

            [
                'nome' => 'Marketing Digital',
                'slug' => 'marketing_digital',
                'status' => 'Ativo',
                'turnos' => ['manha', 'tarde'],
                'permite_estado_diferente' => false,
            ],

            [
                'nome' => 'Análise de Dados e Inteligência Artificial aplicada à Sustentabilidade',
                'slug' => 'analise_dados_ia_sustentabilidade',
                'status' => 'Ativo',
                'turnos' => ['manha', 'tarde', 'noite'],
                'permite_estado_diferente' => true,
            ],

            [
                'nome' => 'Sustentabilidade Digital - Indicadores, Eficiência e Sustentabilidade no Mundo do Trabalho',
                'slug' => 'sustentabilidade_digital',
                'status' => 'Ativo',
                'turnos' => ['manha', 'tarde'],
                'permite_estado_diferente' => true,
            ],
        ];

        foreach ($cursos as $curso) {
            Curso::updateOrCreate(
                ['slug' => $curso['slug']],
                $curso
            );
        }
    }
}
