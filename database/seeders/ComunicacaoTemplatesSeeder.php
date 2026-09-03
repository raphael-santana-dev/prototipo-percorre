<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Comunicacao\Domain\Models\EmailTemplate;
use App\Modules\Comunicacao\Domain\Models\Automacao;

class ComunicacaoTemplatesSeeder extends Seeder
{
    public function run()
    {
        // 1. Template: Nova Inscrição (Candidato)
        $tplInscricao = EmailTemplate::firstOrCreate(
            ['nome' => 'Boas-Vindas e Acesso ao Formulário'],
            [
                'assunto' => 'Bem-vindo! Continue sua inscrição',
                'corpo' => '<p>Olá <strong>[nome_candidato]</strong>,</p><p>Sua inscrição para o curso <strong>[curso_aprovado]</strong> foi iniciada com sucesso.</p><p>Para dar andamento ao processo e enviar seus documentos de matrícula, clique no botão seguro abaixo:</p><p><br>[link_retomada]</p><br><p>Atenciosamente,<br>Equipe Acadêmica.</p>'
            ]
        );

        Automacao::firstOrCreate(
            ['evento_gatilho' => 'inscricao.criada'],
            [
                'nome' => 'Aviso de Nova Inscrição (Link de Retomada)',
                'template_id' => $tplInscricao->id,
                'status' => true
            ]
        );

        // 2. Template: Helpdesk (Aluno -> Professor)
        $tplSolAluno = EmailTemplate::firstOrCreate(
            ['nome' => 'Aviso: Aluno solicitou reabertura de matriz'],
            [
                'assunto' => 'Helpdesk: Nova Solicitação de Aluno',
                'corpo' => '<p>Olá!</p><p>Um estudante acabou de solicitar a reabertura de uma das fases da matriz de avaliação socioemocional vinculada a você.</p><p>Acesse o painel do sistema e navegue até a <strong>Central de Solicitações</strong> para aprovar ou recusar o pedido, avaliando a justificativa informada.</p>'
            ]
        );

        Automacao::firstOrCreate(
            ['evento_gatilho' => 'avaliacao.solicitacao_aluno'],
            [
                'nome' => 'Helpdesk: Notificar Professor sobre Pedido do Aluno',
                'template_id' => $tplSolAluno->id,
                'status' => true
            ]
        );

        // 3. Template: Helpdesk (Professor -> Admin)
        $tplSolAdmin = EmailTemplate::firstOrCreate(
            ['nome' => 'Aviso: Professor solicitou desbloqueio de matriz'],
            [
                'assunto' => 'Helpdesk: Matriz de Avaliação Bloqueada',
                'corpo' => '<p>Olá, Coordenação!</p><p>Um professor solicitou o desbloqueio geral de uma matriz de avaliação que já havia sido finalizada.</p><p>Acesse a <strong>Central de Solicitações</strong> no painel administrativo para analisar os motivos operacionais da requisição e liberar a edição do documento.</p>'
            ]
        );

        Automacao::firstOrCreate(
            ['evento_gatilho' => 'avaliacao.solicitacao_admin'],
            [
                'nome' => 'Helpdesk: Notificar Admin sobre Pedido do Professor',
                'template_id' => $tplSolAdmin->id,
                'status' => true
            ]
        );
    }
}