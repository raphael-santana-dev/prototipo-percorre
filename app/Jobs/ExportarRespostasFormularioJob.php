<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Importacao;
use App\Models\Formulario;
use App\Models\RespostaFormulario;
use App\Models\CampoFormulario;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ExportarRespostasFormularioJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;
    protected $importacaoId;
    protected $formularioId;
    protected $filtros;

    public function __construct($importacaoId, $formularioId, $filtros)
    {
        $this->importacaoId = $importacaoId;
        $this->formularioId = $formularioId;
        $this->filtros = $filtros;
    }

    public function handle()
    {
        $tracking = Importacao::find($this->importacaoId);
        if (!$tracking) return;

        $tracking->update(['status' => 'processando']);
        $formulario = Formulario::find($this->formularioId);
        
        $campos = CampoFormulario::where('formulario_id', $this->formularioId)
            ->whereNotIn('tipo', ['config', 'html', 'divider', 'media', 'social'])
            ->orderBy('etapa')
            ->orderBy('ordem')
            ->get();

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

        $respostas = $query->get();
        
        // Monta o Cabeçalho
        $header = ['Protocolo (ID)', 'Data de Envio'];
        foreach ($campos as $campo) {
            $header[] = $campo->label;
        }

        $dados = [];
        $dados[] = $header;

        $processados = 0;
        foreach ($respostas as $resp) {
            $linha = [
                $resp->id,
                $resp->created_at->format('d/m/Y H:i:s')
            ];

            $respostasSalvas = is_string($resp->respostas) ? json_decode($resp->respostas, true) : ($resp->respostas ?? []);

            foreach ($campos as $campo) {
                $val = $respostasSalvas[$campo->name] ?? '-';
                $linha[] = is_array($val) ? implode(' | ', $val) : (string) $val;
            }

            $dados[] = $linha;
            $processados++;
            
            if ($processados % 100 == 0) {
                $tracking->update(['linhas_processadas' => $processados]);
            }
        }

        $nomeArquivo = 'exportacoes/respostas_' . Str::slug($formulario->titulo) . '_' . date('Ymd_His') . '.' . $tracking->formato;
        
        // Aqui chamamos o exportador Genérico (ajuste conforme a lib que usa no `ExportarInscricoesFiltradasJob`)
        // Se você usar Maatwebsite\Excel, ficaria algo como:
        // \Maatwebsite\Excel\Facades\Excel::store(new \App\Exports\GenericExport($dados), $nomeArquivo, 'public');
        
        // Abordagem nativa para CSV (garantia de funcionar sem lib extra):
        if ($tracking->formato == 'csv') {
            $caminhoCompleto = storage_path('app/public/' . $nomeArquivo);
            if (!file_exists(dirname($caminhoCompleto))) {
                mkdir(dirname($caminhoCompleto), 0755, true);
            }
            $file = fopen($caminhoCompleto, 'w');
            fputs($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8
            foreach ($dados as $linha) { fputcsv($file, $linha, ';'); }
            fclose($file);
        }

        $tracking->update([
            'status' => 'concluido',
            'linhas_processadas' => $processados,
            'arquivo_caminho' => $nomeArquivo
        ]);
    }
}