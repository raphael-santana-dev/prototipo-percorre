<?php

namespace App\Modules\GestaoEducacional\UI\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Modules\Student\Domain\Models\Student;
use App\Modules\GestaoEducacional\Domain\Models\CriterioAvaliacao;
use App\Modules\GestaoEducacional\Domain\Models\PeriodoAvaliacao;
use App\Modules\GestaoEducacional\Domain\Models\PeriodoFase;
use App\Modules\GestaoEducacional\Domain\Models\AlunoAvaliacao;
use App\Modules\GestaoEducacional\Domain\Models\AlunoAvaliacaoItem;
use Spatie\Permission\Models\Role;

class GeradorMock extends Component
{
    public $ambienteGerado = false;
    public $credenciais = [];

    public function gerarAmbienteCompleto()
    {
        DB::beginTransaction();

        try {
            // 1. DADOS ACADÊMICOS BASE (Verifica antes de inserir para evitar erros de duplicidade)
            $unidade = DB::table('unidades')->where('slug', 'sede-principal-mock')->first();
            $unidadeId = $unidade ? $unidade->id : DB::table('unidades')->insertGetId([
                'nome' => 'Sede Principal (Mock)', 
                'slug' => 'sede-principal-mock',
                'endereco' => 'Rua Fictícia, 123',
                'status' => 'Ativa', 
                'created_at' => now()
            ]);
            
            $curso = DB::table('cursos')->where('slug', 'curso-de-tecnologia-mock')->first();
            $cursoId = $curso ? $curso->id : DB::table('cursos')->insertGetId([
                'nome' => 'Curso de Tecnologia Mock', 
                'slug' => 'curso-de-tecnologia-mock',
                'status' => 'Ativo', 
                'created_at' => now()
            ]);
            
            $turno = DB::table('turnos')->where('nome', 'Noturno Mock')->first();
            $turnoId = $turno ? $turno->id : DB::table('turnos')->insertGetId([
                'nome' => 'Noturno Mock',
                'horario_inicio' => '19:00:00',
                'horario_fim' => '22:30:00',
                'status' => true,
                'created_at' => now()
            ]);
            
            $ciclo = DB::table('ciclos')->where('ano', 2026)->where('semestre', 1)->first();
            $cicloId = $ciclo ? $ciclo->id : DB::table('ciclos')->insertGetId([
                'nome' => '2026 - 1º Semestre', 
                'ano' => 2026, 
                'semestre' => 1,
                'data_inicio' => now(),
                'data_fim' => now()->addMonths(6),
                'status' => true, 
                'created_at' => now()
            ]);

            // 2. PROFESSOR
            Role::firstOrCreate(['name' => 'professor', 'guard_name' => 'web']);
            $professor = User::firstOrCreate(
                ['email' => 'professor.mock@sistema.com'],
                ['name' => 'Prof. Avaliador Mock']
            );
            // Força a inserção da senha ignorando bloqueios do $fillable
            $professor->password = Hash::make('senha123');
            $professor->save();
            $professor->assignRole('professor');

            // 3. ESTUDANTE
            $aluno = Student::firstOrCreate(
                ['email' => 'aluno.mock@sistema.com'],
                [
                    'name' => 'Aluno Teste Mock', 
                    'cpf' => '000.000.000-00', 
                    'unidade_id' => $unidadeId,
                    'is_active' => true,
                    'slug' => 'aluno-teste-mock'
                ]
            );
            // Força a inserção da senha ignorando bloqueios do $fillable
            $aluno->password = Hash::make('senha123');
            $aluno->save();

            // 4. TURMA E MATRÍCULA
            $turma = DB::table('turmas')->where('nome', 'Turma Especial de Avaliação')->first();
            $turmaId = $turma ? $turma->id : DB::table('turmas')->insertGetId([
                'nome' => 'Turma Especial de Avaliação',
                'ciclo_id' => $cicloId,
                'curso_id' => $cursoId,
                'unidade_id' => $unidadeId,
                'turno_id' => $turnoId,
                'status' => true,
                'ano' => '2026',
                'created_at' => now()
            ]);

            DB::table('professor_turma')->updateOrInsert(
                ['turma_id' => $turmaId, 'user_id' => $professor->id],
                ['created_at' => now()]
            );

            $matricula = DB::table('matriculas')->where('numero_matricula', 'MOCK2026')->first();
            $matriculaId = $matricula ? $matricula->id : DB::table('matriculas')->insertGetId([
                'numero_matricula' => 'MOCK2026',
                'student_id' => $aluno->id,
                'curso_id' => $cursoId,
                'unidade_id' => $unidadeId,
                'turno_id' => $turnoId,
                'status' => 'ativa',
                'created_at' => now()
            ]);
            
            DB::table('matricula_turma')->updateOrInsert(
                ['matricula_id' => $matriculaId, 'turma_id' => $turmaId],
                ['created_at' => now()]
            );

            // 5. CRITÉRIOS DE AVALIAÇÃO
            $crit1 = CriterioAvaliacao::firstOrCreate(['codigo' => 'COM001'], ['nome' => 'Comunicação Interpessoal', 'status' => true]);
            $crit2 = CriterioAvaliacao::firstOrCreate(['codigo' => 'PRO002'], ['nome' => 'Proatividade e Iniciativa', 'status' => true]);
            $crit3 = CriterioAvaliacao::firstOrCreate(['codigo' => 'RES003'], ['nome' => 'Resolução de Problemas', 'status' => true]);

            // 6. PERÍODO E FASES
            $periodo = PeriodoAvaliacao::firstOrCreate(
                ['ano' => '2026', 'ciclo' => '1'],
                [
                    'data_inicio' => now(),
                    'data_fim' => now()->addDays(30),
                    'status' => '1',
                    'trava_fases' => true
                ]
            );

            $periodo->criterios()->syncWithoutDetaching([$crit1->id, $crit2->id, $crit3->id]);

            PeriodoFase::firstOrCreate(['periodo_id' => $periodo->id, 'fase' => '1'], ['responsavel' => '1']);
            PeriodoFase::firstOrCreate(['periodo_id' => $periodo->id, 'fase' => '2'], ['responsavel' => '2']);
            PeriodoFase::firstOrCreate(['periodo_id' => $periodo->id, 'fase' => '3'], ['responsavel' => '3']);

            // 7. GERAR AS MATRIZES EM BRANCO
            $fasesGeradas = ['1', '2', '3'];
            foreach ($fasesGeradas as $faseStr) {
                $avaliacao = AlunoAvaliacao::firstOrCreate([
                    'periodo_id' => $periodo->id,
                    'student_id' => $aluno->id,
                    'turma_id' => $turmaId,
                    'fase' => $faseStr,
                ], [
                    'status' => '1',
                ]);

                // Só gera os itens em branco se a avaliação for realmente nova
                if ($avaliacao->wasRecentlyCreated) {
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
            }

            DB::commit();

            $this->credenciais = [
                'estudante' => ['login' => 'aluno.mock@sistema.com', 'senha' => 'senha123'],
                'professor' => ['login' => 'professor.mock@sistema.com', 'senha' => 'senha123'],
            ];
            
            $this->ambienteGerado = true;
            $this->dispatch('sucesso', msg: 'Ambiente de avaliação gerado e/ou atualizado com sucesso!');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('erro', msg: 'Erro ao gerar ambiente: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.gestao-educacional.gerador-mock')
            ->layout('components.layouts.app', ['title' => 'Simulador de Integração']);
    }
}