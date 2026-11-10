<?php

namespace App\Modules\Comunicacao\Services;

use App\Models\Inscricao;
use App\Models\SuriContato;
use App\Modules\Comunicacao\Domain\Models\Automacao;
use App\Modules\Comunicacao\Jobs\DispararAutomacaoJob;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class AutomacaoService
{
    public static function disparar(string $eventoGatilho, $destinatario, array $dadosExtras = []): int
    {
        $automacoes = Automacao::with('template')
            ->where('evento_gatilho', $eventoGatilho)
            ->where('status', true)
            ->get();

        if ($automacoes->isEmpty()) {
            return 0;
        }

        $inscricao = null;
        if ($destinatario instanceof Inscricao) {
            $inscricao = $destinatario;
        } elseif (isset($dadosExtras['inscricao']) && $dadosExtras['inscricao'] instanceof Inscricao) {
            $inscricao = $dadosExtras['inscricao'];
            unset($dadosExtras['inscricao']);
        }

        $email = is_string($destinatario)
            ? $destinatario
            : ($destinatario->email ?? $inscricao?->email ?? ($dadosExtras['email'] ?? ''));

        if (!$inscricao && $email !== '') {
            $inscricao = Inscricao::where('email', $email)->latest('id')->first();
        }

        $disparosEnfileirados = 0;

        foreach ($automacoes as $automacao) {
            if (!$automacao->template) {
                continue;
            }

            $contextoEnvio = $dadosExtras;

            if ($automacao->tipo_acao === 'criar_aluno_enviar_email' && $inscricao) {
                if (!$inscricao->student_id) {
                    $senhaPlana = Str::random(8);

                    $estudante = \App\Modules\Student\Domain\Models\Student::firstOrCreate(
                        ['email' => $inscricao->email],
                        [
                            'name'                 => $inscricao->nome,
                            'password'             => Hash::make($senhaPlana),
                            'is_active'            => true,
                            'must_change_password' => true,
                            'unidade_id'           => $inscricao->unidade_id,
                        ]
                    );

                    $inscricao->update(['student_id' => $estudante->id]);

                    $contextoEnvio['senha_provisoria'] = $senhaPlana;
                    $contextoEnvio['link_login']       = url('/aluno/login');
                } else {
                    $contextoEnvio['senha_provisoria'] = '******** (Você já possui cadastro no sistema)';
                    $contextoEnvio['link_login']       = url('/aluno/login');
                }

                // Garante o vínculo do student_id na tabela suri_contatos
                if ($inscricao->student_id) {
                    SuriContato::where('inscricao_id', $inscricao->id)
                        ->update(['student_id' => $inscricao->student_id]);
                }
            }

            $canalDisparo = $automacao->canal_disparo ?? ($automacao->template->canal ?? 'email');

            DispararAutomacaoJob::dispatch(
                $automacao->template,
                (string) $email,
                $inscricao,
                $contextoEnvio,
                $automacao->evento_gatilho,
                $canalDisparo
            );

            $disparosEnfileirados++;
        }

        return $disparosEnfileirados;
    }
}