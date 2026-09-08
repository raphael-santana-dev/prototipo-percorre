<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Comunicacao\Domain\Models\EmailTemplate;

class TemplatesMatriculaSeeder extends Seeder
{
    public function run()
    {
        // 1. Inscrito aprovado sem documento (1º Contato)
        EmailTemplate::firstOrCreate(
            ['nome' => '1. Aprovado sem documento (1º Aviso)'],
            [
                'assunto' => 'Parabéns! Você foi aprovado(a) no Instituto Percorre 🚀',
                'corpo' => '<p>Oi, <strong>{{nome}}</strong>! 💜🎉</p>
                            <p>Parabéns! Você foi aprovado(a) para o curso de <strong>{{curso}}</strong> na unidade <strong>{{unidade}}</strong> do Instituto Percorre! 🚀</p>
                            <p>Para garantir sua vaga, você precisa enviar seus documentos no nosso portal e efetivar sua matrícula:</p>
                            <p>👉 <a href="https://etapadocumentacao-percorre.ai.studio/" target="_blank"><strong>Acessar Portal de Documentação</strong></a></p>
                            <p>⚠️ <strong>Atenção:</strong> essa é uma etapa obrigatória no processo e sua matrícula só será confirmada depois do envio da documentação completa e a validação pelo portal. Se houver pendências, a vaga poderá ser destinada a outro candidato.</p>
                            <p>Lembrando que as aulas começam 11 de agosto. Bora percorrer esse caminho juntos? 💜</p>'
            ]
        );

        // 2. Inscrito aprovado sem documento (Lembrete 20/07)
        EmailTemplate::firstOrCreate(
            ['nome' => '2. Aprovado sem documento (Lembrete)'],
            [
                'assunto' => 'Lembrete: Envie seus documentos para garantir sua vaga ⚠️',
                'corpo' => '<p>Oi, <strong>{{nome}}</strong>! 💜</p>
                            <p>Aqui é do Instituto Percorre, passando para lembrar que ainda não recebemos a sua documentação para confirmar a sua matrícula no curso de <strong>{{curso}}</strong>.</p>
                            <p>⚠️ Como as vagas estão sendo preenchidas por ordem de documentação validada, caso os seus documentos não forem enviados dentro do prazo, sua vaga poderá ser destinada para outro candidato, tá?</p>
                            <p>Não perca essa oportunidade! Envie agora pelo nosso link seguro:</p>
                            <p>👉 <a href="https://etapadocumentacao-percorre.ai.studio/" target="_blank"><strong>Acessar Portal de Documentação</strong></a></p>
                            <p>Estamos torcendo para te ver no Percorre! 🚀</p>'
            ]
        );

        // 3. Inscrito aprovado + documento - assinatura
        EmailTemplate::firstOrCreate(
            ['nome' => '3. Documentos Aprovados - Assinatura do Contrato'],
            [
                'assunto' => 'Falta pouco! Assine seu contrato digital ✍️',
                'corpo' => '<p>Oi, <strong>{{nome}}</strong>! 💜🎉</p>
                            <p>Boas notícias! Seus documentos foram aprovados e você está a um passo de garantir sua vaga no Instituto Percorre.</p>
                            <p>Agora falta apenas assinar o contrato digital. É rápido e essa etapa é obrigatória para confirmar sua matrícula.</p>
                            <p>✍️ Faça a assinatura pelo link:</p>
                            <p>👉 <a href="{{link_assinatura}}" target="_blank"><strong>Assinar Contrato Digital</strong></a></p>
                            <p>⚠️ <strong>Não deixe para depois!</strong> Sua matrícula só será concluída após a assinatura do contrato. Caso essa etapa não seja finalizada dentro do prazo, sua vaga poderá ser destinada a outro candidato.</p>
                            <p>Estamos ansiosos para te receber no Percorre! 🚀</p>'
            ]
        );

        // 4. Matrícula finalizada (Padrão)
        EmailTemplate::firstOrCreate(
            ['nome' => '4. Matrícula Finalizada (Com Detalhes)'],
            [
                'assunto' => 'Matrícula Concluída! Bem-vindo(a) ao Instituto Percorre 🎉',
                'corpo' => '<p>Oi, <strong>{{nome}}</strong>! 💜🎉</p>
                            <p>Parabéns! Sua matrícula foi concluída e sua vaga no Instituto Percorre está confirmada.</p>
                            <p>Agora é só se preparar: as aulas começam no dia 11/08! 🚀</p>
                            <p>Confira as informações da sua turma:</p>
                            <ul style="list-style-type: none; padding-left: 0;">
                                <li>📚 <strong>Curso:</strong> {{curso}}</li>
                                <li>🕘 <strong>Turno:</strong> {{turno}}</li>
                                <li>💻 <strong>Modelo:</strong> {{modelo}}</li>
                                <li>📍 <strong>Endereço:</strong> {{endereco}}</li>
                            </ul>
                            <p>Você está prestes a começar uma jornada que pode transformar o seu futuro, desenvolvendo novas habilidades e se conectando com oportunidades para o mercado de trabalho.</p>
                            <p>E que tal viver essa experiência ao lado de alguém que você conhece? 💜 Convide um amigo para se inscrever no Percorre e começar esse caminho com você! É só acessar o link e compartilhar pelo WhatsApp:</p>
                            <p>👉 <a href="https://bit.ly/traga-um-amigo-percorre" target="_blank">bit.ly/traga-um-amigo-percorre</a></p>
                            <p>Nos vemos em breve! Estamos ansiosos para receber você. 🙌</p>'
            ]
        );

        // 5. Matrícula finalizada (Versão RD Station)
        EmailTemplate::firstOrCreate(
            ['nome' => '5. Matrícula Finalizada (Versão RD / WhatsApp)'],
            [
                'assunto' => 'Matrícula Concluída! Bem-vindo(a) ao Instituto Percorre 🎉',
                'corpo' => '<p>Oi, <strong>{{nome}}</strong>! 💜🎉</p>
                            <p>Parabéns! Sua matrícula foi concluída e sua vaga no Instituto Percorre está confirmada.</p>
                            <p>Agora é só se preparar: as aulas começam no dia 11/08! 🚀</p>
                            <p><strong>Confira as informações da sua turma na mensagem que te enviamos no Whatsapp!</strong></p>
                            <p>Você está prestes a começar uma jornada que pode transformar o seu futuro, desenvolvendo novas habilidades e se conectando com oportunidades para o mercado de trabalho.</p>
                            <p>E que tal viver essa experiência ao lado de alguém que você conhece? 💜 Convide um amigo para se inscrever no Percorre e começar esse caminho com você! É só acessar o link e compartilhar pelo WhatsApp:</p>
                            <p>👉 <a href="https://bit.ly/traga-um-amigo-percorre" target="_blank">bit.ly/traga-um-amigo-percorre</a></p>
                            <p>Nos vemos em breve! Estamos ansiosos para receber você. 🙌</p>'
            ]
        );
    }
}