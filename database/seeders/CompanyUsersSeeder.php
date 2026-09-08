<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CompanyUsersSeeder extends Seeder
{
    public function run(): void
    {
        // Busca todas as empresas já cadastradas pelo EmpresasSeeder
        $empresas = DB::table('empresas')->get();

        if ($empresas->isEmpty()) {
            $this->command->info('Nenhuma empresa encontrada. Por favor, execute o EmpresasSeeder primeiro.');
            return;
        }

        $usuarios = [];
        $senhaPadrao = Hash::make('senha123'); // Senha unificada para facilitar seus testes

        foreach ($empresas as $empresa) {
            $dominio = Str::slug($empresa->nome_fantasia, '') . '.com.br';

            // 1. Contato Principal (Administrador da Empresa)
            $usuarios[] = [
                'name' => 'Admin ' . $empresa->nome_fantasia,
                'email' => 'admin@' . $dominio,
                'password' => $senhaPadrao,
                'documento' => $this->gerarCpfFalso(), // Simulando o CPF do Gestor
                'empresa_id' => $empresa->id,
                'tipo_acesso' => 'contato_principal',
                'is_active' => true,
                'must_change_password' => false, // false para não travar você na tela de redefinir senha agora
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // 2. Gestor Avaliador (Apenas responde as avaliações dos estudantes)
            $usuarios[] = [
                'name' => 'Avaliador ' . $empresa->nome_fantasia,
                'email' => 'avaliador@' . $dominio,
                'password' => $senhaPadrao,
                'documento' => $this->gerarCpfFalso(),
                'empresa_id' => $empresa->id,
                'tipo_acesso' => 'gestor_avaliador',
                'is_active' => true,
                'must_change_password' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Insere ou atualiza os usuários no banco
        foreach ($usuarios as $user) {
            DB::table('company_users')->updateOrInsert(
                ['email' => $user['email']], 
                $user
            );
        }

        $this->command->info(count($usuarios) . ' usuários de empresas foram gerados com sucesso!');
    }

    /**
     * Gera um CPF fake aleatório apenas para preencher o campo 'documento' obrigatório
     */
    private function gerarCpfFalso(): string
    {
        return str_pad(rand(100000000, 999999999), 11, '0', STR_PAD_LEFT);
    }
}