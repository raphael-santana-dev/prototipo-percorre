<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Comunicacao\Domain\Models\EmailTemplate;
use App\Modules\Comunicacao\Domain\Models\Automacao;

class AutomacaoMatriculaSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Cria o Template de E-mail
        $template = EmailTemplate::firstOrCreate(
            ['nome' => 'Convocação para Matrícula (Portal IA)'],
            [
                'assunto' => 'Parabéns, [nome_candidato]! Você foi aprovado(a).',
                'corpo' => '
                    <div style="font-family: sans-serif; color: #333; max-width: 600px; margin: 0 auto; border: 1px solid #eee; border-radius: 8px; overflow: hidden;">
                        <div style="background-color: #6B21A8; padding: 20px; text-align: center;">
                            <h2 style="color: #fff; margin: 0;">Aprovação Confirmada! 🎉</h2>
                        </div>
                        <div style="padding: 30px;">
                            <p>Olá <b>[nome_candidato]</b>,</p>
                            <p>É com grande alegria que informamos sua aprovação para o curso de <b>[curso_aprovado]</b>!</p>
                            <p>Para garantir sua vaga e efetivar sua matrícula, você precisa enviar as fotos dos seus documentos obrigatórios através do nosso portal seguro. Nosso sistema de Inteligência Artificial fará a validação das imagens em tempo real.</p>
                            
                            <div style="text-align: center; margin: 40px 0;">
                                [link_matricula]
                            </div>
                            
                            <p style="font-size: 12px; color: #666;">Se o botão não funcionar, copie este link e cole no seu navegador. Este link é pessoal e intransferível.</p>
                        </div>
                        <div style="background-color: #f9f9f9; padding: 15px; text-align: center; font-size: 12px; color: #888;">
                            Secretaria Acadêmica<br>
                            Este é um e-mail automático, por favor não responda.
                        </div>
                    </div>
                '
            ]
        );

        // 2. Cria a Regra de Automação (Gatilho) vinculada ao Template
        Automacao::firstOrCreate(
            ['evento_gatilho' => 'inscricao.status.aprovado'],
            [
                'nome' => 'Disparo de Link de Matrícula (Aprovação)',
                'template_id' => $template->id,
                'status' => true
            ]
        );
    }
}