<?php

namespace App\Modules\Comunicacao\Services;

use App\Models\Inscricao;
use App\Modules\Comunicacao\Domain\Models\Automacao;
use App\Modules\Comunicacao\Domain\Models\ComunicacaoLog;
use Illuminate\Support\Facades\Schema;

class AntiSpamComunicacaoService
{
    /**
     * Verifica se um candidato já recebeu comunicações (E-mail e/ou WhatsApp Suri)
     * para um determinado template e/ou gatilho.
     *
     * Retorna:
     * [
     *   'ja_recebeu_email'    => bool,
     *   'ja_recebeu_whatsapp' => bool,
     *   'canais_enviados'     => ['email', 'whatsapp'],
     *   'ultimo_envio_em'     => ?string,
     * ]
     */
    public static function diagnosticarEnviosCandidato(
        ?int $inscricaoId,
        ?string $emailDestino = null,
        ?int $templateId = null,
        ?string $gatilho = null
    ): array {
        $temColunaCanal = Schema::hasColumn('comunicacao_logs', 'canal');

        $query = ComunicacaoLog::query()
            ->whereIn('status', ['enviado', 'entregue', 'lido', 'pendente']);

        if ($inscricaoId) {
            $query->where('inscricao_id', $inscricaoId);
        } elseif (!empty($emailDestino)) {
            $query->whereRaw('LOWER(destinatario) = ?', [strtolower(trim($emailDestino))]);
        } else {
            return [
                'ja_recebeu_email'    => false,
                'ja_recebeu_whatsapp' => false,
                'canais_enviados'     => [],
                'ultimo_envio_em'     => null,
            ];
        }

        if ($templateId) {
            $query->where('template_id', $templateId);
        }

        if ($gatilho) {
            $query->where('gatilho', $gatilho);
        }

        $logs = $query->orderBy('id', 'desc')->get();

        $jaEmail = false;
        $jaWpp   = false;

        foreach ($logs as $log) {
            $isWpp = ($temColunaCanal && $log->canal === 'whatsapp')
                || str_contains(strtolower((string) $log->template_nome), 'whatsapp')
                || str_starts_with(strtolower((string) $log->assunto), 'whatsapp:');

            if ($isWpp) {
                $jaWpp = true;
            } else {
                $jaEmail = true;
            }
        }

        $canais = [];
        if ($jaEmail) $canais[] = 'E-mail';
        if ($jaWpp)   $canais[] = 'WhatsApp';

        $ultimo = $logs->first();

        return [
            'ja_recebeu_email'    => $jaEmail,
            'ja_recebeu_whatsapp' => $jaWpp,
            'canais_enviados'     => $canais,
            'ultimo_envio_em'     => $ultimo ? ($ultimo->data_envio ?? $ultimo->created_at)?->format('d/m/Y H:i') : null,
        ];
    }

    /**
     * Verifica uma coleção de inscrições contra todas as automações ativas de um gatilho
     * (E-mail, WhatsApp ou Ambos) e retorna a lista de conflitos para o Modal Anti-Spam.
     */
    public static function verificarConflitosPorGatilho($inscricoes, string $eventoGatilho): array
    {
        $automacoes = Automacao::with('template')
            ->where('evento_gatilho', $eventoGatilho)
            ->where('status', true)
            ->get();

        if ($automacoes->isEmpty()) {
            return [];
        }

        $conflitos = [];

        foreach ($inscricoes as $insc) {
            $canaisConflito = [];
            $templatesConflito = [];
            $ultimaData = null;

            foreach ($automacoes as $auto) {
                if (!$auto->template) continue;

                $canalRegra = $auto->canal_disparo ?: ($auto->template->canal ?? 'email');
                $diag = self::diagnosticarEnviosCandidato($insc->id, $insc->email, $auto->template_id, $eventoGatilho);

                $conflitoEmail = in_array($canalRegra, ['email', 'ambos'], true) && $diag['ja_recebeu_email'];
                $conflitoWpp   = in_array($canalRegra, ['whatsapp', 'ambos'], true) && $diag['ja_recebeu_whatsapp'];

                if ($conflitoEmail) {
                    $canaisConflito[] = 'E-mail';
                }
                if ($conflitoWpp) {
                    $canaisConflito[] = 'WhatsApp';
                }

                if ($conflitoEmail || $conflitoWpp) {
                    $templatesConflito[] = $auto->template->nome;
                    $ultimaData = $diag['ultimo_envio_em'] ?: $ultimaData;
                }
            }

            $canaisConflito = array_values(array_unique($canaisConflito));

            if (!empty($canaisConflito)) {
                $conflitos[] = [
                    'id'          => $insc->id,
                    'nome'        => $insc->nome,
                    'email'       => $insc->email,
                    'celular'     => $insc->celular,
                    'canais'      => $canaisConflito,
                    'canais_str'  => implode(' + ', $canaisConflito),
                    'templates'   => implode(', ', array_unique($templatesConflito)),
                    'ultimo_envio'=> $ultimaData,
                ];
            }
        }

        return $conflitos;
    }

    /**
     * Retorna o resumo de disparo do gatilho 'inscricao.finalizada' para exibir na coluna da listagem.
     */
    public static function obterStatusDisparoFinalizacao(Inscricao $inscricao): array
    {
        $diag = self::diagnosticarEnviosCandidato($inscricao->id, $inscricao->email, null, 'inscricao.finalizada');

        if ($diag['ja_recebeu_email'] && $diag['ja_recebeu_whatsapp']) {
            return [
                'tipo'  => 'ambos',
                'label' => 'Ambos (E-mail + WPP)',
                'data'  => $diag['ultimo_envio_em'],
            ];
        }

        if ($diag['ja_recebeu_whatsapp']) {
            return [
                'tipo'  => 'whatsapp',
                'label' => 'WhatsApp Enviado',
                'data'  => $diag['ultimo_envio_em'],
            ];
        }

        if ($diag['ja_recebeu_email']) {
            return [
                'tipo'  => 'email',
                'label' => 'E-mail Enviado',
                'data'  => $diag['ultimo_envio_em'],
            ];
        }

        return [
            'tipo'  => 'nenhum',
            'label' => 'Não Disparado',
            'data'  => null,
        ];
    }
}