<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FormularioFase;
use App\Models\Inscricao;
use App\Models\RespostaFormulario;
use App\Models\CampoFormulario;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class ProcessarLiberacaoFasesCommand extends Command
{
    protected $signature = 'formularios:processar-fases-liberadas';
    protected $description = 'Verifica fases de formulários/ciclos recém-liberadas e dispara as automações de e-mail para quem iniciou o preenchimento.';

    public function handle(): int
    {
        $agora = now();

        $fasesPendentes = FormularioFase::with(['ciclo', 'formulario'])
            ->whereNull('notificacao_enviada_em')
            ->where('data_liberacao', '<=', $agora)
            ->where(function ($q) use ($agora) {
                $q->whereNull('data_encerramento')
                  ->orWhere('data_encerramento', '>=', $agora);
            })
            ->get();

        if ($fasesPendentes->isEmpty()) {
            return self::SUCCESS;
        }

        foreach ($fasesPendentes as $fase) {
            try {
                // Marca imediatamente para evitar concorrência entre workers
                $fase->update(['notificacao_enviada_em' => now()]);

                if ($fase->ciclo_id && $fase->ciclo) {
                    $this->processarDisparoCiclo($fase);
                } elseif ($fase->formulario_id && $fase->formulario) {
                    $this->processarDisparoFormularioPublico($fase);
                }
            } catch (\Throwable $e) {
                Log::error("Erro ao processar disparo de liberação da Fase #{$fase->id}: " . $e->getMessage());
            }
        }

        return self::SUCCESS;
    }

    private function processarDisparoCiclo(FormularioFase $fase): void
    {
        $paginasFase = is_array($fase->paginas) ? array_map('intval', $fase->paginas) : [];
        $maiorPaginaFase = !empty($paginasFase) ? max($paginasFase) : 99;

        // Todos os candidatos do ciclo que iniciaram o preenchimento, têm e-mail válido
        // e ainda precisam preencher esta fase (não finalizaram tudo em 99 nem estão em espera 100)
        $inscricoes = Inscricao::with(['curso', 'unidade', 'turno', 'ciclo'])
            ->where('ciclo_id', $fase->ciclo_id)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->whereNotIn('etapa_atual', [99, 100])
            ->where('etapa_atual', '<=', $maiorPaginaFase)
            ->get();

        if ($inscricoes->isEmpty()) {
            return;
        }

        $gatilhos = $fase->obterGatilhosDisparo();

        foreach ($inscricoes as $inscricao) {
            if (!filter_var($inscricao->email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $token = Crypt::encrypt($inscricao->id);
            $urlRetomada = route('inscricao.retomar', ['token' => $token]);

            $dadosExtras = [
                'nome_candidato'    => $inscricao->nome,
                'nome_fase'         => $fase->nome,
                'nome_ciclo'        => $fase->ciclo->nome ?? '',
                'paginas_fase'      => implode(', ', $paginasFase),
                'data_liberacao'    => $fase->data_liberacao ? $fase->data_liberacao->format('d/m/Y H:i') : '',
                'data_encerramento' => $fase->data_encerramento ? $fase->data_encerramento->format('d/m/Y H:i') : 'Sem data limite',
                'link_retomada'     => $urlRetomada,
                'url_formulario'    => $urlRetomada,
                'email_template_id' => $fase->email_template_id,
            ];

            // 1. Dispara todas as automações cadastradas para os gatilhos desta fase
            foreach ($gatilhos as $eventoGatilho) {
                \App\Modules\Comunicacao\Services\AutomacaoService::disparar($eventoGatilho, $inscricao, $dadosExtras);
            }

            // 2. Se o usuário selecionou um template diretamente no modal da fase e o AutomacaoService possuir método direto
            if ($fase->email_template_id && method_exists(\App\Modules\Comunicacao\Services\AutomacaoService::class, 'dispararTemplateDireto')) {
                \App\Modules\Comunicacao\Services\AutomacaoService::dispararTemplateDireto(
                    (int) $fase->email_template_id,
                    $inscricao,
                    $dadosExtras
                );
            }
        }
    }

    private function processarDisparoFormularioPublico(FormularioFase $fase): void
    {
        $formulario = $fase->formulario;
        $paginasFase = is_array($fase->paginas) ? array_map('intval', $fase->paginas) : [];
        $maiorPaginaFase = !empty($paginasFase) ? max($paginasFase) : 99;

        // Descobre possíveis campos de e-mail dinâmicos deste formulário
        $camposEmailNames = CampoFormulario::where('formulario_id', $formulario->id)
            ->where('subtipo', 'email')
            ->pluck('name')
            ->toArray();

        $respostasIniciadas = RespostaFormulario::where('formulario_id', $formulario->id)
            ->where(function ($q) use ($maiorPaginaFase) {
                $q->whereNull('etapa_parada')
                  ->orWhere('etapa_parada', '<=', $maiorPaginaFase);
            })
            ->get();

        if ($respostasIniciadas->isEmpty()) {
            return;
        }

        $gatilhos = $fase->obterGatilhosDisparo();
        $urlFormulario = url('/f/' . $formulario->slug);

        foreach ($respostasIniciadas as $reg) {
            $respostasArr = is_string($reg->respostas) ? json_decode($reg->respostas, true) : ($reg->respostas ?? []);
            if (!is_array($respostasArr)) {
                $respostasArr = [];
            }

            // Localiza o e-mail de quem iniciou o preenchimento
            $emailDestinatario = $respostasArr['_email_coletado'] ?? null;

            if (empty($emailDestinatario)) {
                foreach ($camposEmailNames as $cName) {
                    if (!empty($respostasArr[$cName]) && filter_var($respostasArr[$cName], FILTER_VALIDATE_EMAIL)) {
                        $emailDestinatario = $respostasArr[$cName];
                        break;
                    }
                }
            }

            if (empty($emailDestinatario) && $reg->user_id) {
                $user = \App\Models\User::find($reg->user_id);
                $emailDestinatario = $user?->email;
            }

            if (empty($emailDestinatario) || !filter_var($emailDestinatario, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $dadosExtras = [
                'nome_fase'         => $fase->nome,
                'titulo_formulario' => $formulario->titulo,
                'paginas_fase'      => implode(', ', $paginasFase),
                'data_liberacao'    => $fase->data_liberacao ? $fase->data_liberacao->format('d/m/Y H:i') : '',
                'data_encerramento' => $fase->data_encerramento ? $fase->data_encerramento->format('d/m/Y H:i') : 'Sem data limite',
                'url_formulario'    => $urlFormulario,
                'link_retomada'     => $urlFormulario,
                'email_template_id' => $fase->email_template_id,
            ];

            foreach ($gatilhos as $eventoGatilho) {
                \App\Modules\Comunicacao\Services\AutomacaoService::disparar($eventoGatilho, $emailDestinatario, $dadosExtras);
            }

            if ($fase->email_template_id && method_exists(\App\Modules\Comunicacao\Services\AutomacaoService::class, 'dispararTemplateDireto')) {
                \App\Modules\Comunicacao\Services\AutomacaoService::dispararTemplateDireto(
                    (int) $fase->email_template_id,
                    $emailDestinatario,
                    $dadosExtras
                );
            }
        }
    }
}