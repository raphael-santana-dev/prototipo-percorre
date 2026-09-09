<?php

namespace App\Modules\Comunicacao\Services;

use App\Models\User;
use App\Models\Inscricao;
use App\Modules\Comunicacao\Domain\Models\Automacao;
use App\Modules\Comunicacao\Jobs\DispararAutomacaoJob;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class AutomacaoService
{
    public static function disparar(string $eventoGatilho, $destinatario, array $dadosExtras = [])
    {
        $automacoes = Automacao::with('template')->where('evento_gatilho', $eventoGatilho)->where('status', true)->get();
        if ($automacoes->isEmpty()) return;

        $email = is_string($destinatario) ? $destinatario : ($destinatario->email ?? null);
        if (!$email) return;

        $inscricao = null;
        if ($destinatario instanceof Inscricao) {
            $inscricao = $destinatario;
        } elseif (isset($dadosExtras['inscricao'])) {
            $inscricao = $dadosExtras['inscricao'];
            unset($dadosExtras['inscricao']); 
        } else {
            $inscricao = Inscricao::where('email', $email)->latest()->first();
        }

        foreach ($automacoes as $automacao) {
            $contextoEnvio = $dadosExtras;

            // MÁGICA: Se a automação manda criar o aluno, cria no banco antes de mandar o e-mail!
            if ($automacao->tipo_acao === 'criar_aluno_enviar_email' && $inscricao) {
                if (!$inscricao->student_id) {
                    $senhaPlana = Str::random(8); // Gera senha provisória

                    // Cria o acesso do Aluno
                    $estudante = \App\Modules\Student\Domain\Models\Student::firstOrCreate(
                        ['email' => $inscricao->email],
                        [
                            'name' => $inscricao->nome,
                            'password' => Hash::make($senhaPlana),
                            'is_active' => true,
                            'must_change_password' => true
                        ]
                    );

                    // Vincula o acesso à inscrição
                    $inscricao->update(['student_id' => $estudante->id]);

                    // Passa a senha para o e-mail
                    $contextoEnvio['senha_provisoria'] = $senhaPlana;
                    $contextoEnvio['link_login'] = url('/aluno/login');
                } else {
                    $contextoEnvio['senha_provisoria'] = '******** (Você já possui cadastro no sistema)';
                    $contextoEnvio['link_login'] = url('/aluno/login');
                }
            }

            if ($automacao->template) {
                DispararAutomacaoJob::dispatch($automacao->template, $email, $inscricao, $contextoEnvio);
            }
        }
    }
}