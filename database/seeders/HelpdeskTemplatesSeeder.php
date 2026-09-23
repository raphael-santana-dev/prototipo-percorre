<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Comunicacao\Domain\Models\EmailTemplate;
use App\Modules\Comunicacao\Domain\Models\Automacao;

class HelpdeskTemplatesSeeder extends Seeder
{
    public function run()
    {
        $t2 = EmailTemplate::firstOrCreate(['nome' => 'Admin: Solicitação de Novo Cadastro'], [
            'assunto' => 'Liberação de Cadastro de Inscrição',
            'corpo' => '<p>Olá!</p><p>O usuário <strong>{{nome_solicitante}}</strong> solicitou a inclusão de uma nova inscrição via painel e precisa de autorização.</p><p><strong>Justificativa:</strong> {{justificativa}}</p><br>{{link_painel}}'
        ]);
        Automacao::firstOrCreate(['evento_gatilho' => 'inscricao.solicitacao_cadastro'], ['nome' => 'Novo Cadastro', 'template_id' => $t2->id, 'status' => true]);

        $t3 = EmailTemplate::firstOrCreate(['nome' => 'Admin: Aluno pede reabertura'], [
            'assunto' => 'Solicitação de Aluno',
            'corpo' => '<p>Olá!</p><p>O estudante <strong>{{nome_solicitante}}</strong> solicitou a reabertura de uma fase de avaliação.</p><p><strong>Justificativa:</strong> {{justificativa}}</p><br>{{link_painel}}'
        ]);
        Automacao::firstOrCreate(['evento_gatilho' => 'avaliacao.solicitacao_aluno'], ['nome' => 'Pedido do Aluno', 'template_id' => $t3->id, 'status' => true]);

        $t4 = EmailTemplate::firstOrCreate(['nome' => 'Admin: Professor pede reabertura de matriz'], [
            'assunto' => 'Matriz Bloqueada',
            'corpo' => '<p>Olá!</p><p>O professor <strong>{{nome_solicitante}}</strong> solicitou o desbloqueio geral de uma matriz finalizada.</p><p><strong>Motivo:</strong> {{justificativa}}</p><br>{{link_painel}}'
        ]);
        Automacao::firstOrCreate(['evento_gatilho' => 'avaliacao.solicitacao_admin'], ['nome' => 'Pedido do Professor', 'template_id' => $t4->id, 'status' => true]);
    }
}