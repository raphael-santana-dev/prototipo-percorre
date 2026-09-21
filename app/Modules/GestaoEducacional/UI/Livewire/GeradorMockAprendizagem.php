<?php

namespace App\Modules\GestaoEducacional\UI\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\CicloAprendizagem;
use App\Models\CicloAprendizagemFase;
use App\Models\AlunoCicloAprendizagem;
use App\Modules\Student\Domain\Models\Student;
use App\Modules\Company\Domain\Models\Empresa;
use App\Modules\Company\Domain\Models\CompanyUser;
use Faker\Factory as Faker;

class GeradorMockAprendizagem extends Component
{
    public $ambienteGerado = false;
    public $quantidadeInjecao = 1;
    public $dadosGerados = [];

    public function mount()
    {
        abort_if(!feature('ferramenta.mock'), 403, 'Gerador desativado.');
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('ferramenta.mock'), 403, 'Acesso restrito.');
    }

    public function gerarAmbienteCompleto()
    {
        $this->validate(['quantidadeInjecao' => 'required|integer|min:1|max:50']);

        $faker = Faker::create('pt_BR');
        DB::beginTransaction();

        try {
            $ciclo = CicloAprendizagem::firstOrCreate(
                ['ano' => date('Y'), 'ciclo_mes' => '05'],
                [
                    'nome' => 'Avaliação Semestral Aprendizes - ' . date('Y') . '/05',
                    'data_inicio' => now()->startOfMonth(),
                    'data_fim' => now()->endOfMonth(),
                    'prazo_dias' => 10,
                    'data_fechamento' => now()->addDays(15),
                    'status' => true
                ]
            );

            $faseAprendiz = CicloAprendizagemFase::firstOrCreate(
                ['ciclo_aprendizagem_id' => $ciclo->id, 'ordem' => 1],
                ['nome' => 'Fase 1 - Autoavaliação do Aprendiz', 'respondedores_permitidos' => ['student']]
            );

            $faseEmpresa = CicloAprendizagemFase::firstOrCreate(
                ['ciclo_aprendizagem_id' => $ciclo->id, 'ordem' => 2],
                ['nome' => 'Fase 2 - Avaliação do Gestor', 'respondedores_permitidos' => ['company']]
            );

            $this->dadosGerados = [];

            for ($i = 0; $i < $this->quantidadeInjecao; $i++) {
                
                $empresa = Empresa::create([
                    'razao_social' => $faker->company . ' LTDA',
                    'nome_fantasia' => $faker->company,
                    'cnpj' => $faker->unique()->numerify('##############'),
                    'is_active' => true
                ]);

                $gestorEmail = 'gestor.' . $faker->unique()->numerify('####') . '@empresa.com';
                $gestor = CompanyUser::create([
                    'name' => 'Gestor ' . $faker->firstName,
                    'email' => $gestorEmail,
                    'documento' => $faker->unique()->numerify('###########'),
                    'empresa_id' => $empresa->id,
                    'tipo_acesso' => 'gestor_avaliador',
                    'is_active' => true,
                    'password' => Hash::make('senha123')
                ]);

                $alunoEmail = 'aprendiz.' . $faker->unique()->numerify('####') . '@sistema.com';
                $aluno = Student::create([
                    'name' => $faker->name,
                    'email' => $alunoEmail,
                    'cpf' => $faker->unique()->numerify('###########'),
                    'is_active' => true,
                    'password' => Hash::make('senha123'),
                    'slug' => Str::slug($faker->name . '-' . Str::random(4)),
                    'empresa_id' => $empresa->id,
                    'gestor_id' => $gestor->id,
                    'is_aprendiz' => true,
                    'aprendizagem_ios' => 1, 
                    'data_inicio_contrato' => now()->subMonths(3)->format('Y-m-d'),
                    'data_fim_contrato' => now()->addMonths(9)->format('Y-m-d')
                ]);

                $faseAtual = rand(1, 2) == 1 ? $faseAprendiz->id : $faseEmpresa->id;
                
                AlunoCicloAprendizagem::create([
                    'ciclo_aprendizagem_id' => $ciclo->id,
                    'student_id' => $aluno->id,
                    'fase_atual_id' => $faseAtual,
                    'status' => '2',
                    'data_geracao' => now()->subDays(2),
                    'data_envio' => now()->subDays(1),
                    'data_prazo' => now()->addDays(8),
                ]);

                $formSlug = $faseAprendiz->formularios->first()->slug ?? '';
                $cpfFormatado = preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $aluno->cpf);
                
                $dadosExtras = [
                    'ciclo_mes_ano' => $ciclo->ciclo_mes . '/' . $ciclo->ano,
                    'turma_codigo_nome' => 'Turma Mock - 001',
                    'aluno_ra' => 'MOCK-' . rand(1000, 9999),
                    'aluno_nome' => $aluno->name,
                    'aluno_cpf' => $cpfFormatado,
                    'link_avaliacao' => route('formulario-aprendizagem.responder', ['slug' => $formSlug, 'aluno_id' => $aluno->id])
                ];

                \App\Modules\Comunicacao\Services\AutomacaoService::disparar('aprendizagem.nova_avaliacao_equipe', 'aprendizagem_aluno@ios.org.br', $dadosExtras);
                \App\Modules\Comunicacao\Services\AutomacaoService::disparar('aprendizagem.nova_avaliacao_aluno', $aluno->email, $dadosExtras);
                \App\Modules\Comunicacao\Services\AutomacaoService::disparar('aprendizagem.nova_avaliacao_empresa', $gestor->email, $dadosExtras);

                $this->dadosGerados[] = [
                    'aluno_nome' => $aluno->name,
                    'empresa_nome' => $empresa->nome_fantasia,
                    'gestor_email' => $gestor->email,
                    'fase' => $faseAtual == $faseAprendiz->id ? '1 - Aguardando Aprendiz' : '2 - Aguardando Gestor'
                ];
            }

            DB::commit();
            $this->ambienteGerado = true;
            $this->dispatch('sucesso', msg: "{$this->quantidadeInjecao} fluxos de aprendizagem injetados!");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('erro', msg: 'Erro ao gerar ambiente: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.gestao-educacional.gerador-mock-aprendizagem')
            ->layout('components.layouts.app', ['title' => 'Simulador de Avaliação de Aprendizagem']);
    }
}