<?php

namespace App\Modules\Comunicacao\Services;

use App\Models\User;
use App\Models\Inscricao;
use App\Modules\Comunicacao\Domain\Models\Automacao;
use App\Modules\Comunicacao\Jobs\DispararAutomacaoJob;

class AutomacaoService
{
    public static function disparar(string $eventoGatilho, $destinatario, array $dadosExtras = [])
    {
        $automacoes = Automacao::with('template')->where('evento_gatilho', $eventoGatilho)->where('status', true)->get();
        if ($automacoes->isEmpty()) return;

        // Aceita o Model User, Model Inscrição ou um endereço de e-mail em texto (String)
        $email = is_string($destinatario) ? $destinatario : ($destinatario->email ?? null);
        if (!$email) return;

        $user = $destinatario instanceof User ? $destinatario : User::where('email', $email)->first();
        $inscricao = $destinatario instanceof Inscricao ? $destinatario : Inscricao::where('email', $email)->latest()->first();

        // Se uma inscrição for passada manualmente pelos dados extras, dê preferência a ela
        if (isset($dadosExtras['inscricao'])) $inscricao = $dadosExtras['inscricao'];

        $contexto = array_merge([
            'user' => $user, 
            'inscricao' => $inscricao
        ], $dadosExtras);

        foreach ($automacoes as $automacao) {
            if ($automacao->template) {
                DispararAutomacaoJob::dispatch($automacao->template, $email, $contexto);
            }
        }
    }
}