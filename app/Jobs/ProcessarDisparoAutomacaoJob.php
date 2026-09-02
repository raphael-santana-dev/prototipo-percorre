<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Modules\Comunicacao\Domain\Models\Automacao;
use App\Modules\Comunicacao\Domain\Models\Comunicado;
use App\Models\Inscricao;
use App\Modules\Comunicacao\Services\EmailParserService;
use Illuminate\Support\Facades\Mail;

class ProcessarDisparoAutomacaoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $automacao;
    public $inscricao;

    public function __construct(Automacao $automacao, Inscricao $inscricao)
    {
        $this->automacao = $automacao;
        $this->inscricao = $inscricao;
    }

    public function handle(): void
    {
        $template = $this->automacao->template;
        if (!$template || empty($this->inscricao->email)) return;

        $corpoParseado = EmailParserService::parseTexto($template->corpo, $this->inscricao);
        $assuntoParseado = str_replace('[nome_candidato]', $this->inscricao->nome, $template->assunto);

        $comunicado = Comunicado::create([
            'template_id' => $template->id,
            'destinatarios' => [$this->inscricao->email],
            'status' => 'enviando'
        ]);

        try {
            Mail::html($corpoParseado, function ($message) use ($assuntoParseado) {
                $message->to($this->inscricao->email)
                        ->subject($assuntoParseado);
            });
            $comunicado->update(['status' => 'concluido']);
        } catch (\Exception $e) {
            $comunicado->update(['status' => 'erro']);
        }
    }
}