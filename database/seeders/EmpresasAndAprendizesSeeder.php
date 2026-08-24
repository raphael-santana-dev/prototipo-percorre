<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Modules\Company\Domain\Models\Empresa;
use App\Modules\Company\Domain\Models\CompanyUser;
use App\Modules\Student\Domain\Models\Student;

class EmpresasAndAprendizesSeeder extends Seeder
{
    public function run(): void
    {
        $senhaPadrao = Hash::make('mudar123');

        // Cria Empresa A
        $empresaA = Empresa::create([
            'razao_social' => 'Tech Solutions S.A.',
            'nome_fantasia' => 'Tech Solutions',
            'cnpj' => '11222333000199',
            'is_active' => true,
        ]);

        // Cria o Contato Principal da Empresa A
        CompanyUser::create([
            'name' => 'Ana Diretora (Tech)', 'email' => 'ana@tech.com', 'password' => $senhaPadrao,
            'documento' => '11111111111', 'empresa_id' => $empresaA->id, 'tipo_acesso' => 'contato_principal',
        ]);

        // Cria 2 Gestores para a Empresa A
        $gestorA1 = CompanyUser::create(['name' => 'Carlos Gestor', 'email' => 'carlos@tech.com', 'password' => $senhaPadrao, 'documento' => '22222222222', 'empresa_id' => $empresaA->id, 'tipo_acesso' => 'gestor_avaliador']);
        $gestorA2 = CompanyUser::create(['name' => 'Bruno Gestor', 'email' => 'bruno@tech.com', 'password' => $senhaPadrao, 'documento' => '33333333333', 'empresa_id' => $empresaA->id, 'tipo_acesso' => 'gestor_avaliador']);

        // Cria Empresa B
        $empresaB = Empresa::create([
            'razao_social' => 'Logistica Brasil LTDA',
            'nome_fantasia' => 'LogBrasil',
            'cnpj' => '44555666000188',
            'is_active' => true,
        ]);

        // Cria o Contato Principal da Empresa B
        CompanyUser::create([
            'name' => 'Marcos Diretor (Log)', 'email' => 'marcos@log.com', 'password' => $senhaPadrao,
            'documento' => '44444444444', 'empresa_id' => $empresaB->id, 'tipo_acesso' => 'contato_principal',
        ]);
        
        $gestorB1 = CompanyUser::create(['name' => 'Julia Gestora', 'email' => 'julia@log.com', 'password' => $senhaPadrao, 'documento' => '55555555555', 'empresa_id' => $empresaB->id, 'tipo_acesso' => 'gestor_avaliador']);

        // Pega 10 alunos comuns no banco (que não são aprendizes) e os transforma
        $alunos = Student::where('is_aprendiz', false)->inRandomOrder()->take(10)->get();

        if ($alunos->count() > 0) {
            foreach ($alunos->take(5) as $aluno) {
                $aluno->update([
                    'is_aprendiz' => true,
                    'empresa_id' => $empresaA->id,
                    'gestor_id' => rand(0, 1) ? $gestorA1->id : $gestorA2->id // Randomiza o gestor
                ]);
            }
            foreach ($alunos->skip(5)->take(5) as $aluno) {
                $aluno->update([
                    'is_aprendiz' => true,
                    'empresa_id' => $empresaB->id,
                    'gestor_id' => $gestorB1->id
                ]);
            }
        }
    }
}