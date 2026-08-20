<?php

namespace App\Modules\GestaoEducacional\UI\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Turma;
use App\Models\Matricula;
use App\Modules\Student\Domain\Models\Student;
use App\Modules\GestaoEducacional\Domain\Models\CriterioAvaliacao;
use App\Modules\GestaoEducacional\Domain\Models\PeriodoAvaliacao;
use App\Modules\GestaoEducacional\Domain\Models\PeriodoFase;
use App\Modules\GestaoEducacional\Domain\Models\AlunoAvaliacao;
use App\Modules\GestaoEducacional\Domain\Models\AlunoAvaliacaoItem;
use Spatie\Permission\Models\Role;
use Faker\Factory as Faker;

class GeradorMock extends Component
{
    public $ambienteGerado = false;
    public $quantidadeInjecao = 1; // Input do usuário
    public $alunosGerados = []; // Guarda as credenciais geradas no loop

    public function gerarAmbienteCompleto()
    {
        $this->validate([
            'quantidadeInjecao' => 'required|integer|min:1|max:100'
        ]);

        $faker = Faker::create('pt_BR');
        DB::beginTransaction();

        try {
            // 1. GARANTIR A ESTRUTURA BASE (Usa existente ou cria 1 padrão)
            $unidadeId = DB::table('unidades')->inRandomOrder()->value('id') ?? DB::table('unidades')->insertGetId([
                'nome' => 'Sede Principal (Mock)', 'slug' => 'sede-principal-mock-' . Str::random(4), 'endereco' => 'Rua Mock, 123', 'status' => 'Ativa', 'created_at' => now()
            ]);
            
            $cursoId = DB::table('cursos')->inRandomOrder()->value('id') ?? DB::table('cursos')->insertGetId([
                'nome' => 'Curso de Tecnologia Mock', 'slug' => 'curso-tec-mock-' . Str::random(4), 'status' => 'Ativo', 'created_at' => now()
            ]);
            
            $turnoId = DB::table('turnos')->inRandomOrder()->value('id') ?? DB::table('turnos')->insertGetId([
                'nome' => 'Noturno Mock ' . Str::random(3), 'horario_inicio' => '19:00:00', 'horario_fim' => '22:30:00', 'status' => true, 'created_at' => now()
            ]);
            
            $cicloId = DB::table('ciclos')->inRandomOrder()->value('id') ?? DB::table('ciclos')->insertGetId([
                'nome' => '2026 - 1º Semestre', 'ano' => 2026, 'semestre' => 1, 'data_inicio' => now(), 'data_fim' => now()->addMonths(6), 'status' => true, 'created_at' => now()
            ]);

            // 2. GARANTIR UM PERÍODO DE AVALIAÇÃO ABERTO E CRITÉRIOS
            $crit1 = CriterioAvaliacao::firstOrCreate(['codigo' => 'COM001'], ['nome' => 'Comunicação Interpessoal', 'status' => true]);
            $crit2 = CriterioAvaliacao::firstOrCreate(['codigo' => 'PRO002'], ['nome' => 'Proatividade e Iniciativa', 'status' => true]);
            $crit3 = CriterioAvaliacao::firstOrCreate(['codigo' => 'RES003'], ['nome' => 'Resolução de Problemas', 'status' => true]);

            $periodo = PeriodoAvaliacao::firstOrCreate(
                ['ano' => '2026', 'ciclo' => '1'],
                ['data_inicio' => now(), 'data_fim' => now()->addDays(30), 'status' => '1', 'trava_fases' => true]
            );
            $periodo->criterios()->syncWithoutDetaching([$crit1->id, $crit2->id, $crit3->id]);

            PeriodoFase::firstOrCreate(['periodo_id' => $periodo->id, 'fase' => '1'], ['responsavel' => '1']);
            PeriodoFase::firstOrCreate(['periodo_id' => $periodo->id, 'fase' => '2'], ['responsavel' => '2']);
            PeriodoFase::firstOrCreate(['periodo_id' => $periodo->id, 'fase' => '3'], ['responsavel' => '3']);

            // 3. GARANTIR UM PROFESSOR
            Role::firstOrCreate(['name' => 'professor', 'guard_name' => 'web']);
            $professor = User::role('professor')->inRandomOrder()->first();
            if (!$professor) {
                $professor = User::create(['name' => 'Prof. ' . $faker->lastName, 'email' => 'prof.' . Str::random(5) . '@sistema.com', 'password' => Hash::make('senha123')]);
                $professor->assignRole('professor');
            }

            $this->alunosGerados = []; // Limpa o array de credenciais para a tela

            // ==========================================
            // LOOP DE INJEÇÃO DINÂMICA
            // ==========================================
            for ($i = 0; $i < $this->quantidadeInjecao; $i++) {
                
                // Cria Estudante Único
                $alunoEmail = 'aluno.' . $faker->unique()->numerify('#####') . '@sistema.com';
                $aluno = Student::create([
                    'name' => $faker->name, 
                    'email' => $alunoEmail,
                    'cpf' => $faker->unique()->numerify('###########'), 
                    'unidade_id' => $unidadeId,
                    'is_active' => true,
                    'password' => Hash::make('senha123'),
                    'slug' => Str::slug($faker->name . '-' . Str::random(4))
                ]);

                // Pega uma Turma Aleatória ou cria se não existir nenhuma
                $turma = Turma::inRandomOrder()->first();
                if (!$turma || rand(1, 10) > 8) { // 20% de chance de criar uma turma nova para variar os dados
                    $turma = Turma::create([
                        'nome' => 'Turma ' . $faker->bothify('?##'),
                        'ciclo_id' => $cicloId,
                        'curso_id' => $cursoId,
                        'unidade_id' => $unidadeId,
                        'turno_id' => $turnoId,
                        'status' => true,
                        'ano' => '2026',
                    ]);
                    DB::table('professor_turma')->insert(['turma_id' => $turma->id, 'user_id' => $professor->id, 'created_at' => now()]);
                } else {
                    // Garante que o professor está na turma selecionada
                    DB::table('professor_turma')->updateOrInsert(['turma_id' => $turma->id, 'user_id' => $professor->id], ['created_at' => now()]);
                }

                // Cria Matrícula
                $matriculaId = DB::table('matriculas')->insertGetId([
                    'numero_matricula' => 'MAT' . $faker->unique()->numerify('######'),
                    'student_id' => $aluno->id,
                    'curso_id' => $cursoId,
                    'unidade_id' => $unidadeId,
                    'turno_id' => $turnoId,
                    'status' => 'ativa',
                    'created_at' => now()
                ]);
                DB::table('matricula_turma')->insert(['matricula_id' => $matriculaId, 'turma_id' => $turma->id, 'created_at' => now()]);

                // Gera as Matrizes de Avaliação
                foreach (['1', '2', '3'] as $faseStr) {
                    $avaliacao = AlunoAvaliacao::create([
                        'periodo_id' => $periodo->id,
                        'student_id' => $aluno->id,
                        'turma_id' => $turma->id,
                        'fase' => $faseStr,
                        'status' => '1',
                    ]);

                    $itensData = [];
                    foreach ([$crit1, $crit2, $crit3] as $crit) {
                        $itensData[] = [
                            'aluno_avaliacao_id' => $avaliacao->id,
                            'criterio_id' => $crit->id,
                            'nivel_nps' => null,
                            'aval_metas' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                    AlunoAvaliacaoItem::insert($itensData);
                }

                // Salva a credencial gerada para exibir na View
                $this->alunosGerados[] = [
                    'nome' => $aluno->name,
                    'login' => $aluno->email,
                    'turma' => $turma->nome
                ];
            }

            DB::commit();
            $this->ambienteGerado = true;
            $this->dispatch('sucesso', msg: "{$this->quantidadeInjecao} avaliações injetadas com sucesso!");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('erro', msg: 'Erro ao gerar ambiente: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.gestao-educacional.gerador-mock')
            ->layout('components.layouts.app', ['title' => 'Simulador de Integração em Lote']);
    }
}