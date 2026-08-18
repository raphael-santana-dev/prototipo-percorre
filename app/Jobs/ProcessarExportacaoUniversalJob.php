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

    public $timeout = 3600; // Até 1 hora para planilhas gigantes
    protected $exportacao;

    public function __construct(Importacao $exportacao)
    {
        $this->exportacao = $exportacao;
    }

    public function handle(): void
    {
        try {
            $this->exportacao->update(['status' => 'processando']);
            
            // Define o nome e onde o arquivo será salvo (Disco Public para o usuário conseguir baixar)
            $nomeArquivo = "Exportacao_" . ucfirst($this->exportacao->tipo) . "_" . now()->format('Ymd_His') . "." . $this->exportacao->formato;
            $caminhoRelativo = "exportacoes/{$nomeArquivo}";
            $caminhoAbsoluto = Storage::disk('public')->path($caminhoRelativo);

            // Garante que a pasta existe
            if (!file_exists(dirname($caminhoAbsoluto))) {
                mkdir(dirname($caminhoAbsoluto), 0755, true);
            }

            // Seleciona a Query baseado no tipo que o usuário escolheu
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

            // Inicia o "Escritor" do Excel/CSV
            $writer = SimpleExcelWriter::create($caminhoAbsoluto);

            $linhaAtual = 0;

            // O cursor() lê do banco 1 linha por vez e descarta da memória! Perfeito para Big Data.
            foreach ($query->cursor() as $registro) {
                $linhaAtual++;

                $linhaProcessada = match ($this->exportacao->tipo) {
                    'inscricoes' => $this->mapearInscricao($registro),
                    'usuarios' => $this->mapearUsuario($registro),
                    'campos' => $this->mapearCampo($registro),
                };

                $writer->addRow($linhaProcessada);

                // Atualiza a barra de progresso a cada 100 linhas escritas
                if ($linhaAtual % 100 === 0) {
                    $this->exportacao->update(['linhas_processadas' => $linhaAtual]);
                }
            }

            // Encerra o arquivo
            $writer->close();

            // Marca como concluído e disponibiliza a URL para Download
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

    // =========================================================================
    // MAPEADORES DE COLUNAS (FORMATAM OS DADOS PARA O EXCEL)
    // =========================================================================

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

        // Achata as respostas do JSON para colunas separadas
        if (is_array($dadosDinamicos)) {
            foreach ($dadosDinamicos as $pergunta => $resposta) {
                // Se for array (ex: múltiplos checkboxes), transforma em texto separado por vírgula
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
            // 'Grupos' => $registro->roles->pluck('name')->implode(', ') // Exemplo de se você quiser puxar a role usando Spatie
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