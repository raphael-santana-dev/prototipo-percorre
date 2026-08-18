<?php

namespace App\Modules\Comunicacao\Services;

use App\Models\User;
use App\Models\Inscricao;
use App\Modules\Comunicacao\Domain\Models\Automacao;
use App\Modules\Comunicacao\Jobs\DispararAutomacaoJob;

class AutomacaoService
{
    /**
     * TUTORIAL DE USO: Como acionar uma automação no seu código?
     * 
     * Em vez de usar Observers, você chama essa função exatamente onde a ação ocorre 
     * (ex: No Livewire/Controller onde o administrador clica em "Aprovar Inscrição").
     * 
     * EXEMPLO DE USO:
     * \App\Modules\Comunicacao\Services\AutomacaoService::disparar('inscricao.aprovada', $inscricao);
     */
    public static function disparar(string $eventoGatilho, $entidade)
    {
        // 1. Busca se existe alguma automação ATIVA cadastrada para este evento
        $automacoes = Automacao::with('template')
            ->where('evento_gatilho', $eventoGatilho)
            ->where('status', true)
            ->get();

        if ($automacoes->isEmpty()) {
            return; // Se não houver automação ligada para este evento, não faz nada
        }

        // 2. Extrai o E-mail e monta o contexto (dependendo se a entidade é User ou Inscrição)
        $email = $entidade->email ?? null;
        if (!$email) return;

        $user = $entidade instanceof User ? $entidade : User::where('email', $email)->first();
        $inscricao = $entidade instanceof Inscricao ? $entidade : Inscricao::where('email', $email)->latest()->first();

        $contexto = ['user' => $user, 'inscricao' => $inscricao];

        // 3. Para cada automação ativa neste evento, manda para a fila de envio
        foreach ($automacoes as $automacao) {
            if ($automacao->template) {
                // Dispara o Job para não travar a tela do usuário
                DispararAutomacaoJob::dispatch($automacao->template, $email, $contexto);
            }
        }
    }
}