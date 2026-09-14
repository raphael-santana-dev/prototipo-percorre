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
use App\Models\Importacao;
use App\Models\User;
use App\Models\Inscricao;
use App\Models\CampoFormulario;

class ProcessarExportacaoUniversalJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600;
    protected $exportacao;

    public function __construct(Importacao $exportacao)
    {
        $this->exportacao = $exportacao;
    }

    public function handle(): void
    {
        try {
            $this->exportacao->update(['status' => 'processando']);
            
            $nomeArquivo = "Exportacao_" . ucfirst($this->exportacao->tipo) . "_" . now()->format('Ymd_His') . "." . $this->exportacao->formato;
            $caminhoRelativo = "exportacoes/{$nomeArquivo}";
            $caminhoAbsoluto = Storage::disk('public')->path($caminhoRelativo);

            if (!file_exists(dirname($caminhoAbsoluto))) {
                mkdir(dirname($caminhoAbsoluto), 0755, true);
            }

            $query = match ($this->exportacao->tipo) {
                'inscricoes' => Inscricao::query(),
                'usuarios' => User::query(),
                'campos' => CampoFormulario::query(),
                default => throw new \Exception("Tipo de exportação '{$this->exportacao->tipo}' não implementado."),
            };

            $totalLinhas = $query->count();
            $this->exportacao->update(['total_linhas' => $totalLinhas]);

            if ($totalLinhas === 0) {
                throw new \Exception("Não há registros no banco de dados para exportar.");
            }

            $writer = SimpleExcelWriter::create($caminhoAbsoluto);

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
                'arquivo_gerado_caminho' => $caminhoRelativo
            ]);

        } catch (\Throwable $e) {
            $this->exportacao->update([
                'status' => 'erro', 
                'erro_mensagem' => json_encode([['linha' => 'Geração', 'mensagem' => $e->getMessage()]], JSON_UNESCAPED_UNICODE)
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
                $base["Resposta: " . ucfirst(str_replace('_', ' ', $pergunta))] = is_array($resposta) ? implode(', ', $resposta) : $resposta;
            }
        }

        return $base;
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