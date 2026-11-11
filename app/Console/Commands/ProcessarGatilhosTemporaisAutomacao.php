<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ciclo;
use App\Models\Inscricao;
use App\Models\Formulario;
use App\Models\FormularioFase;
use App\Models\CampoFormulario;
use App\Models\RespostaFormulario;
use App\Modules\Comunicacao\Domain\Models\Automacao;
use App\Modules\Comunicacao\Jobs\DispararAutomacaoJob;
use App\Modules\Comunicacao\Services\AntiSpamComunicacaoService;

class ProcessarGatilhosTemporaisAutomacao extends Command
{
    protected $signature = 'automacoes:processar-gatilhos-temporais';
    protected $description = 'Processa gatilhos de N dias para o fim da inscrição e abandono de formulário (inscrição e público).';

    public function handle(): void
    {
        $this->processarFimProcessoInscricao();
        $this->processarAbandonoInscricao();
        $this->processarAbandonoFormularioPublico();
    }

    /**
     * 1. GATILHO: Quando faltar N dias para o fim do processo de inscrição (inscricao.fim_proximo)
     */
    private function processarFimProcessoInscricao(): void
    {
        $regras = Automacao::with('template')
            ->where('evento_gatilho', 'inscricao.fim_proximo')
            ->where('status', true)
            ->get();

        if ($regras->isEmpty()) return;

        $hoje = now()->startOfDay();
        $ciclosAtivos = Ciclo::where('status', true)->whereNotNull('data_fim')->get();

        foreach ($regras as $regra) {
            if (!$regra->template) continue;

            $diasAlvo = max(1, (int) ($regra->parametro_dias ?: 3));
            $gatilhoEspecifico = "inscricao.fim_proximo.{$diasAlvo}d";

            foreach ($ciclosAtivos as $ciclo) {
                $dataFim = $ciclo->data_fim->copy()->startOfDay();
                $diasRestantes = (int) $hoje->diffInDays($dataFim, false);

                if ($diasRestantes !== $diasAlvo) {
                    continue;
                }

                // Busca candidatos que iniciaram a inscrição neste ciclo e ainda não finalizaram (etapa_atual < 99)
                $inscricoes = Inscricao::where('ciclo_id', $ciclo->id)
                    ->whereNotIn('etapa_atual', [99, 100])
                    ->get();

                foreach ($inscricoes as $insc) {
                    $diag = AntiSpamComunicacaoService::diagnosticarEnviosCandidato(
                        $insc->id,
                        $insc->email,
                        $regra->template_id,
                        $gatilhoEspecifico
                    );

                    if ($diag['ja_recebeu_email'] && $diag['ja_recebeu_whatsapp']) {
                        continue;
                    }

                    DispararAutomacaoJob::dispatch(
                        $regra->template,
                        (string) $insc->email,
                        $insc,
                        [
                            'dias_restantes'     => (string) $diasAlvo,
                            'data_fim_inscricao' => $ciclo->data_fim->format('d/m/Y'),
                        ],
                        $gatilhoEspecifico,
                        $regra->canal_disparo ?: ($regra->template->canal ?? 'email'),
                        false
                    );
                }
            }
        }
    }

    /**
     * 2. GATILHO: Abandono de Preenchimento de Inscrição (inscricao.abandono)
     * Somente quando TODAS as fases do ciclo já estiverem liberadas e o candidato não finalizou.
     */
    private function processarAbandonoInscricao(): void
    {
        $regras = Automacao::with('template')
            ->where('evento_gatilho', 'inscricao.abandono')
            ->where('status', true)
            ->get();

        if ($regras->isEmpty()) return;

        $agora = now();
        $ciclosAtivos = Ciclo::where('status', true)
            ->where('data_inicio', '<=', $agora)
            ->where('data_fim', '>=', $agora)
            ->get();

        foreach ($ciclosAtivos as $ciclo) {
            // Verifica se existem fases futuras ainda bloqueadas neste ciclo
            $temFaseBloqueadaFutura = FormularioFase::where('ciclo_id', $ciclo->id)
                ->where('data_liberacao', '>', $agora)
                ->exists();

            // Só caracteriza abandono total se todas as fases já estiverem liberadas
            if ($temFaseBloqueadaFutura) {
                continue;
            }

            foreach ($regras as $regra) {
                if (!$regra->template) continue;

                $diasInatividade = max(1, (int) ($regra->parametro_dias ?: 1));
                $limiteInatividade = $agora->copy()->subDays($diasInatividade);

                $inscricoesAbandonadas = Inscricao::where('ciclo_id', $ciclo->id)
                    ->whereNotIn('etapa_atual', [99, 100])
                    ->where('updated_at', '<=', $limiteInatividade)
                    ->get();

                foreach ($inscricoesAbandonadas as $insc) {
                    DispararAutomacaoJob::dispatch(
                        $regra->template,
                        (string) $insc->email,
                        $insc,
                        ['dias_inativo' => (string) $diasInatividade],
                        'inscricao.abandono',
                        $regra->canal_disparo ?: ($regra->template->canal ?? 'email'),
                        false
                    );
                }
            }
        }
    }

    /**
     * 3. GATILHO: Abandono de Preenchimento de Formulário Público (formulario.abandono)
     * Somente quando TODAS as fases do formulário já estiverem liberadas e o participante não finalizou.
     */
    private function processarAbandonoFormularioPublico(): void
    {
        $regras = Automacao::with('template')
            ->where('evento_gatilho', 'formulario.abandono')
            ->where('status', true)
            ->get();

        if ($regras->isEmpty()) return;

        $agora = now();
        $formulariosAtivos = Formulario::where('status', true)->get();

        foreach ($formulariosAtivos as $form) {
            $temFaseBloqueadaFutura = FormularioFase::where('formulario_id', $form->id)
                ->where('data_liberacao', '>', $agora)
                ->exists();

            if ($temFaseBloqueadaFutura) {
                continue;
            }

            $totalEtapasForm = max(1, (int) (CampoFormulario::where('formulario_id', $form->id)
                ->where('tipo', '!=', 'config')
                ->max('etapa') ?? 1));

            if ($totalEtapasForm <= 1) {
                continue;
            }

            $camposEmail = CampoFormulario::where('formulario_id', $form->id)->where('subtipo', 'email')->pluck('name')->toArray();
            $camposTel   = CampoFormulario::where('formulario_id', $form->id)->where('subtipo', 'tel')->pluck('name')->toArray();

            foreach ($regras as $regra) {
                if (!$regra->template) continue;

                $diasInatividade = max(1, (int) ($regra->parametro_dias ?: 1));
                $limiteInatividade = $agora->copy()->subDays($diasInatividade);

                $respostasIncompletas = RespostaFormulario::where('formulario_id', $form->id)
                    ->where('etapa_parada', '<', $totalEtapasForm)
                    ->where('updated_at', '<=', $limiteInatividade)
                    ->get();

                foreach ($respostasIncompletas as $resp) {
                    $dadosResp = is_array($resp->respostas)
                        ? $resp->respostas
                        : (json_decode($resp->respostas ?? '{}', true) ?: []);

                    $email = $dadosResp['_email_coletado'] ?? null;
                    if (!$email) {
                        foreach ($camposEmail as $cName) {
                            if (!empty($dadosResp[$cName]) && filter_var($dadosResp[$cName], FILTER_VALIDATE_EMAIL)) {
                                $email = $dadosResp[$cName];
                                break;
                            }
                        }
                    }

                    $celular = $dadosResp['celular'] ?? $dadosResp['telefone'] ?? null;
                    if (!$celular) {
                        foreach ($camposTel as $tName) {
                            if (!empty($dadosResp[$tName])) {
                                $celular = $dadosResp[$tName];
                                break;
                            }
                        }
                    }

                    if (!$email && !$celular) continue;

                    $nomeParticipante = $dadosResp['nome'] ?? $dadosResp['nome_completo'] ?? 'Participante';
                    $inscricaoRelacionada = $email ? Inscricao::whereRaw('LOWER(email) = ?', [strtolower($email)])->latest('id')->first() : null;

                    DispararAutomacaoJob::dispatch(
                        $regra->template,
                        (string) ($email ?: ''),
                        $inscricaoRelacionada,
                        array_merge($dadosResp, [
                            'nome'              => $nomeParticipante,
                            'celular'           => $celular,
                            'titulo_formulario' => $form->titulo,
                            'url_formulario'    => url('/f/' . $form->slug),
                        ]),
                        "formulario.abandono.{$form->id}.resp.{$resp->id}",
                        $regra->canal_disparo ?: ($regra->template->canal ?? 'email'),
                        false
                    );
                }
            }
        }
    }
}