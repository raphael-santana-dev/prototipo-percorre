<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use App\Models\Importacao;
use App\Models\Formulario;
use App\Models\RespostaFormulario;
use App\Models\CampoFormulario;
use Illuminate\Support\Str;
use Spatie\SimpleExcel\SimpleExcelWriter;

class ExportarRespostasFormularioJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // Tempo extra para grandes volumes
    public $trackingId;
    public $formularioId;
    public $filtros;

    public function __construct($trackingId, $formularioId, $filtros)
    {
        $this->trackingId = $trackingId;
        $this->formularioId = $formularioId;
        $this->filtros = $filtros;
    }

    public function handle(): void
    {
        $tracking = Importacao::find($this->trackingId);
        if (!$tracking) return;

        $tracking->update(['status' => 'processando']);
        $formulario = Formulario::find($this->formularioId);
        
        // 1. Busca os campos do formulário (que serão as colunas da planilha)
        $campos = CampoFormulario::where('formulario_id', $this->formularioId)
            ->whereNotIn('tipo', ['config', 'html', 'divider', 'media', 'social'])
            ->orderBy('etapa')
            ->orderBy('ordem')
            ->get();

        // 2. Monta a Query aplicando ordenação e busca passados pela tela
        $query = RespostaFormulario::where('formulario_id', $this->formularioId);

        if (!empty($this->filtros['search'])) {
            $search = $this->filtros['search'];
            $query->where(function($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhere('respostas', 'like', "%{$search}%");
            });
        }

        $sortField = $this->filtros['sortField'] ?? 'created_at';
        $sortDirection = $this->filtros['sortDirection'] ?? 'desc';
        $query->orderBy($sortField, $sortDirection);

        // Processamos as respostas em chunk para economizar RAM caso tenham muitas
        $respostas = $query->get();

        // 3. Configura o Spatie SimpleExcel
        $fileName = 'respostas_' . Str::slug($formulario->titulo ?? 'formulario') . '_' . time() . '.' . $tracking->formato;
        $caminhoRelativo = 'exportacoes/' . $fileName;
        Storage::disk('public')->makeDirectory('exportacoes');
        $caminhoAbsoluto = Storage::disk('public')->path($caminhoRelativo);

        $writer = SimpleExcelWriter::create($caminhoAbsoluto);
        if ($tracking->formato === 'csv') {
            $writer->useDelimiter(';');
        }

        $linhasProcessadas = 0;

        // 4. Escreve os dados no arquivo linha por linha
        foreach ($respostas as $resp) {
            // Mapeia colunas fixas
            $linha = [
                'Protocolo (ID)' => $resp->id,
                'Data de Envio' => $resp->created_at->format('d/m/Y H:i:s'),
            ];

            // Mapeia colunas dinâmicas (respostas do JSON)
            $respostasSalvas = is_string($resp->respostas) ? json_decode($resp->respostas, true) : ($resp->respostas ?? []);

            foreach ($campos as $campo) {
                $val = $respostasSalvas[$campo->name] ?? '-';
                // Garante que arrays (ex: múltiplos checkboxes) virem texto corrido
                $linha[$campo->label] = is_array($val) ? implode(' | ', $val) : (string) $val;
            }

            // O SimpleExcelWriter lê as chaves da Array associativa e monta o cabeçalho automaticamente na 1º iteração
            $writer->addRow($linha);

            $linhasProcessadas++;
            if ($linhasProcessadas % 50 === 0) {
                $tracking->update(['linhas_processadas' => $linhasProcessadas]);
            }
        }

        // 5. Finaliza a importação no banco
        $tracking->update([
            'status' => 'concluido',
            'linhas_processadas' => $linhasProcessadas,
            'arquivo_gerado_caminho' => $caminhoRelativo
        ]);
    }
}