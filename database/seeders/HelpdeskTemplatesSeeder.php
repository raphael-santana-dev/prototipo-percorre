<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Comunicacao\Domain\Models\EmailTemplate;
use App\Modules\Comunicacao\Domain\Models\Automacao;

class HelpdeskTemplatesSeeder extends Seeder
{
    public function run()
    {
        // 1. Template: Nova Inscrição (Candidato)
        $t1 = EmailTemplate::firstOrCreate(['nome' => 'Boas-Vindas e Formulário'], [
            'assunto' => 'Bem-vindo! Continue sua inscrição',
            'corpo' => '<p>Olá <strong>[nome_candidato]</strong>,</p><p>Para concluir sua inscrição, clique no botão seguro abaixo:</p><p><br>[link_retomada]</p><br><p>Equipe Acadêmica.</p>'
        ]);
        Automacao::firstOrCreate(['evento_gatilho' => 'inscricao.criada'], ['nome' => 'Aviso de Nova Inscrição', 'template_id' => $t1->id, 'status' => true]);

        // 2. Template: Helpdesk (Cadastro de Inscrição)
        $t2 = EmailTemplate::firstOrCreate(['nome' => 'Admin: Solicitação de Novo Cadastro'], [
            'assunto' => 'Helpdesk: Liberação de Cadastro de Inscrição',
            'corpo' => '<p>Olá!</p><p>O usuário <strong>[nome_solicitante]</strong> solicitou a inclusão de uma nova inscrição via painel e precisa de autorização.</p><p><strong>Justificativa:</strong> [justificativa]</p><br>[link_painel]'
        ]);
        Automacao::firstOrCreate(['evento_gatilho' => 'inscricao.solicitacao_cadastro'], ['nome' => 'Helpdesk: Novo Cadastro', 'template_id' => $t2->id, 'status' => true]);

        // 3. Template: Helpdesk (Aluno -> Professor)
        $t3 = EmailTemplate::firstOrCreate(['nome' => 'Admin: Aluno pede reabertura'], [
            'assunto' => 'Helpdesk: Solicitação de Aluno',
            'corpo' => '<p>Olá!</p><p>O estudante <strong>[nome_solicitante]</strong> solicitou a reabertura de uma fase de avaliação.</p><p><strong>Justificativa:</strong> [justificativa]</p><br>[link_painel]'
        ]);
        Automacao::firstOrCreate(['evento_gatilho' => 'avaliacao.solicitacao_aluno'], ['nome' => 'Helpdesk: Pedido do Aluno', 'template_id' => $t3->id, 'status' => true]);

        // 4. Template: Helpdesk (Professor -> Admin)
        $t4 = EmailTemplate::firstOrCreate(['nome' => 'Admin: Professor pede reabertura de matriz'], [
            'assunto' => 'Helpdesk: Matriz Bloqueada',
            'corpo' => '<p>Olá!</p><p>O professor <strong>[nome_solicitante]</strong> solicitou o desbloqueio geral de uma matriz finalizada.</p><p><strong>Motivo:</strong> [justificativa]</p><br>[link_painel]'
        ]);
        Automacao::firstOrCreate(['evento_gatilho' => 'avaliacao.solicitacao_admin'], ['nome' => 'Helpdesk: Pedido do Professor', 'template_id' => $t4->id, 'status' => true]);
    }
}