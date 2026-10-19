<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\SimpleExcel\SimpleExcelWriter;
use App\Models\SystemTask;
use App\Models\User;
use App\Models\Inscricao;
use App\Models\CampoFormulario;

class ProcessarExportacaoUniversalJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600;
    protected $exportacao;

    public function __construct(SystemTask $exportacao)
    {
        $this->exportacao = $exportacao;
    }

    public function handle(): void
    {
        try {
            $this->exportacao->update([
                'status' => 'processando',
                'started_at' => now()
            ]);
            
            $nomeArquivo = "Exportacao_" . ucfirst($this->exportacao->tipo) . "_" . now()->format('Ymd_His') . "." . $this->exportacao->formato;
            $caminhoRelativo = "exportacoes/{$nomeArquivo}";
            $caminhoAbsoluto = Storage::disk('public')->path($caminhoRelativo);

            if (!file_exists(dirname($caminhoAbsoluto))) {
                mkdir(dirname($caminhoAbsoluto), 0755, true);
            }

            $query = match ($this->exportacao->tipo) {
                'inscricoes' => \App\Models\Inscricao::query(),
                'usuarios' => \App\Models\User::query(),
                'campos' => \App\Models\CampoFormulario::query(),
                default => throw new \Exception("Tipo de exportação '{$this->exportacao->tipo}' não implementado."),
            };

            $totalLinhas = $query->count();
            $this->exportacao->update(['total_linhas' => $totalLinhas]);

            if ($totalLinhas === 0) {
                throw new \Exception("Não há registros no banco de dados para exportar.");
            }

            $writer = \Spatie\SimpleExcel\SimpleExcelWriter::create($caminhoAbsoluto);

            $linhaAtual = 0;
            foreach ($query->cursor() as $registro) {
                $linhaAtual++;

                $linhaProcessada = match ($this->exportacao->tipo) {
                    'inscricoes' => $this->mapearInscricao($registro),
                    'usuarios' => $this->mapearUsuario($registro),
                    'campos' => $this->mapearCampo($registro),
                };

                $writer->addRow($linhaProcessada);

                if ($linhaAtual % 100 === 0) {
                    $this->exportacao->update(['linhas_processadas' => $linhaAtual]);
                }
            }

            $writer->close();

            $this->exportacao->update([
                'status' => 'concluido',
                'linhas_processadas' => $linhaAtual,
                'arquivo_gerado_caminho' => $caminhoRelativo,
                'finished_at' => now()
            ]);

        } catch (\Throwable $e) {
            $this->exportacao->update([
                'status' => 'erro', 
                'erro_mensagem' => json_encode([['linha' => 'Geração', 'mensagem' => $e->getMessage()]], JSON_UNESCAPED_UNICODE),
                'finished_at' => now()
            ]);
        }
    }

    private function mapearInscricao($registro)
    {
        $dadosDinamicos = is_string($registro->dados_dinamicos) ? json_decode($registro->dados_dinamicos, true) : ($registro->dados_dinamicos ?? []);
        
        $base = [
            'ID' => $registro->id,
            'Nome Completo' => $registro->nome,
            'CPF' => $registro->cpf,
            'E-mail' => $registro->email,
            'Celular' => $registro->celular,
            'Data de Nascimento' => $registro->data_nascimento ? \Carbon\Carbon::parse($registro->data_nascimento)->format('d/m/Y') : '',
            'Status' => $registro->status_inscricao_id,
            'Pontuação' => $registro->pontuacao_total,
            'Criado Em' => $registro->created_at->format('d/m/Y H:i:s'),
        ];

        if (is_array($dadosDinamicos)) {
            foreach ($dadosDinamicos as $pergunta => $resposta) {
                if (str_contains(strtolower($pergunta), 'form_config') || str_starts_with(strtolower($pergunta), 'ui_')) {
                    continue;
                }
                $base["Resposta: " . ucfirst(str_replace('_', ' ', $pergunta))] = $this->formatarValorParaExportacao($resposta);
            }
        }

        return $base;
    }

    private function formatarValorParaExportacao($val): string
    {
        if (is_array($val)) {
            $itens = array_map(fn($item) => $this->formatarValorParaExportacao($item), array_filter($val, fn($v) => $v !== null && $v !== ''));
            return empty($itens) ? '' : implode(', ', $itens);
        }

        $valStr = trim((string) $val);
        if ($valStr === '') {
            return '';
        }

        if (str_starts_with($valStr, 'data:image/')) {
            $hash = md5($valStr);
            $caminhoAssinatura = "assinaturas/sig_{$hash}.png";

            if (!Storage::disk('public')->exists($caminhoAssinatura)) {
                $partes = explode(',', $valStr, 2);
                if (isset($partes[1])) {
                    Storage::disk('public')->put($caminhoAssinatura, base64_decode($partes[1]));
                }
            }

            return asset('storage/' . $caminhoAssinatura);
        }

        if (preg_match('/\/(uploads|documentos|links)\//', $valStr)) {
            $caminhos = explode(',', $valStr);
            $urls = array_map(fn($p) => asset('storage/' . ltrim(trim($p), '/')), array_filter($caminhos));
            return implode(', ', $urls);
        }

        return $valStr;
    }

    private function mapearUsuario($registro)
    {
        return [
            'ID' => $registro->id,
            'Nome' => $registro->name,
            'E-mail' => $registro->email,
            'CPF' => $registro->cpf,
            'Criado Em' => $registro->created_at->format('d/m/Y H:i:s'),
        ];
    }

    private function mapearCampo($registro)
    {
        return [
            'ID' => $registro->id,
            'Ciclo ID' => $registro->ciclo_id,
            'Etapa' => $registro->etapa,
            'Ordem' => $registro->ordem,
            'Label' => $registro->label,
            'Name (ID no Banco)' => $registro->name,
            'Tipo' => $registro->tipo,
            'Obrigatório' => $registro->obrigatorio ? 'Sim' : 'Não',
        ];
    }
}