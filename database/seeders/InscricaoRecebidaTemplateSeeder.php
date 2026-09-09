<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Comunicacao\Domain\Models\EmailTemplate;
use App\Modules\Comunicacao\Domain\Models\Automacao;

class InscricaoRecebidaTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $template = EmailTemplate::firstOrCreate(
            ['nome' => 'Inscrição Recebida — Acesso ao Portal do Aluno'],
            [
                'assunto' => 'Inscrição Confirmada! Acesse seu Portal do Aluno 💜🚀',
                'corpo' => '<p>Oi, <strong>{{nome}}</strong>! 💜🎉</p>
<p>Recebemos a sua inscrição para o curso de <strong>{{curso}}</strong> no Instituto Percorre com sucesso!</p>
<p>Para você acompanhar o andamento do processo seletivo, envio de documentos e futuras comunicações, criamos o seu acesso exclusivo ao <strong>Portal do Aluno</strong>.</p>
<div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin: 24px 0;">
    <p style="margin-top: 0; font-weight: bold; color: #1e293b; font-size: 15px;">Suas Credenciais de Acesso:</p>
    <p style="margin: 6px 0; color: #334155;"><strong>Endereço de Acesso:</strong> <a href="{{link_login}}" target="_blank" style="color: #6b21a8; font-weight: bold;">{{link_login}}</a></p>
    <p style="margin: 6px 0; color: #334155;"><strong>Login:</strong> Seu e-mail cadastrado</p>
    <p style="margin: 6px 0; color: #334155;"><strong>Senha Provisória:</strong> <code style="background-color: #ede9fe; color: #6b21a8; padding: 3px 8px; border-radius: 4px; font-weight: bold; font-family: monospace;">{{senha_provisoria}}</code></p>
    <div style="margin-top: 18px;">
        <a href="{{link_login}}" target="_blank" style="display: inline-block; background-color: #7c3aed; color: #ffffff; text-decoration: none; font-weight: bold; padding: 10px 22px; border-radius: 6px; font-size: 13px;">
            Entrar no Portal do Aluno
        </a>
    </div>
</div>
<p>⚠️ <strong>Atenção:</strong> Por motivos de segurança, você será orientado(a) a cadastrar uma nova senha no seu primeiro acesso.</p>
<p>Fique atento(a) ao seu e-mail e às datas das próximas fases. Estamos muito felizes em ver você percorrendo esse caminho conosco! 🙌</p>'
            ]
        );

        Automacao::firstOrCreate(
            ['evento_gatilho' => 'inscricao.finalizada'],
            [
                'nome' => 'Criar Estudante e Enviar Acesso ao Finalizar Inscrição',
                'template_id' => $template->id,
                'tipo_acao' => 'criar_aluno_enviar_email',
                'status' => true
            ]
        );
    }
}