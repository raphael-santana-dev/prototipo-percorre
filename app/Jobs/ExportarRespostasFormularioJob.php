<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use App\Models\SystemTask;
use App\Models\Formulario;
use App\Models\RespostaFormulario;
use App\Models\CampoFormulario;
use App\Models\Curso;
use App\Modules\Unidade\Domain\Models\Unidade;
use App\Modules\Turno\Domain\Models\Turno;
use Illuminate\Support\Str;
use Spatie\SimpleExcel\SimpleExcelWriter;

class ExportarRespostasFormularioJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600;
    public $trackingId;
    public $formularioId;
    public $filtros;

    public function __construct($trackingId, $formularioId, $filtros = [])
    {
        $this->trackingId = $trackingId;
        $this->formularioId = $formularioId;
        $this->filtros = is_array($filtros) ? $filtros : [];
    }

    public function handle(): void
    {
        $tracking = SystemTask::find($this->trackingId);
        if (!$tracking) return;

        try {
            $tracking->update([
                'status'     => 'processando',
                'started_at' => now(),
            ]);

            $formulario = Formulario::findOrFail($this->formularioId);

            $campos = CampoFormulario::where('formulario_id', $this->formularioId)
                ->whereNotIn('tipo', ['config', 'html', 'divider', 'media', 'spacer', 'link'])
                ->orderBy('etapa')
                ->orderBy('ordem')
                ->get();

            $query = RespostaFormulario::where('formulario_id', $this->formularioId);

            if (!empty($this->filtros['ids']) && is_array($this->filtros['ids'])) {
                $query->whereIn('id', $this->filtros['ids']);
            }

            if (!empty($this->filtros['search'])) {
                $search = trim((string) $this->filtros['search']);
                $query->where(function ($q) use ($search) {
                    $q->whereRaw('CAST(id AS TEXT) LIKE ?', ["%{$search}%"])
                      ->orWhereRaw('CAST(respostas AS TEXT) ILIKE ?', ["%{$search}%"]);
                });
            }

            if (!empty($this->filtros['data_inicio'])) {
                $query->whereDate('created_at', '>=', $this->filtros['data_inicio']);
            }
            if (!empty($this->filtros['data_fim'])) {
                $query->whereDate('created_at', '<=', $this->filtros['data_fim']);
            }

            // Aplica os filtros dinâmicos por pergunta
            if (!empty($this->filtros['filtrosDinamicos']) && is_array($this->filtros['filtrosDinamicos'])) {
                foreach ($this->filtros['filtrosDinamicos'] as $nomeCampo => $valorFiltro) {
                    if ($valorFiltro === null || $valorFiltro === '') continue;

                    $campoObj = $campos->firstWhere('name', $nomeCampo);
                    if (!$campoObj) continue;

                    if (in_array($campoObj->subtipo, ['date', 'datetime-local'], true)) {
                        if (is_array($valorFiltro)) {
                            if (!empty($valorFiltro['de'])) {
                                $query->whereRaw("respostas->>? >= ?", [$nomeCampo, $valorFiltro['de']]);
                            }
                            if (!empty($valorFiltro['ate'])) {
                                $query->whereRaw("respostas->>? <= ?", [$nomeCampo, $valorFiltro['ate']]);
                            }
                        }
                    } elseif (in_array($campoObj->tipo, ['select', 'radio'], true)) {
                        $query->whereRaw("respostas->>? = ?", [$nomeCampo, $valorFiltro]);
                    } else {
                        $query->whereRaw("respostas->>? ILIKE ?", [$nomeCampo, "%{$valorFiltro}%"]);
                    }
                }
            }

            $sortField = $this->filtros['sortField'] ?? 'created_at';
            $sortDirection = $this->filtros['sortDirection'] ?? 'desc';
            $query->orderBy($sortField, $sortDirection);

            $totalLinhas = (clone $query)->count();
            $tracking->update(['total_linhas' => $totalLinhas]);

            $formato = strtolower($tracking->formato ?: 'xlsx');
            $fileName = 'respostas_' . Str::slug($formulario->titulo ?? 'formulario') . '_' . time() . '.' . $formato;
            $caminhoRelativo = 'exportacoes/' . $fileName;

            Storage::disk('public')->makeDirectory('exportacoes');
            $caminhoAbsoluto = Storage::disk('public')->path($caminhoRelativo);

            $isCsv = ($formato === 'csv');
            $file = null;
            $writer = null;

            if ($isCsv) {
                $file = fopen($caminhoAbsoluto, 'w');
                fputs($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8 para Excel
            } else {
                $writer = SimpleExcelWriter::create($caminhoAbsoluto);
            }

            $linhasProcessadas = 0;
            $cabecalhosEscritos = false;

            foreach ($query->cursor() as $resp) {
                $respostasSalvas = is_string($resp->respostas)
                    ? (json_decode($resp->respostas, true) ?? [])
                    : ($resp->respostas ?? []);

                $linha = [
                    'Protocolo (ID)' => $resp->id,
                    'Data de Envio'  => $resp->created_at ? $resp->created_at->format('d/m/Y H:i:s') : '-',
                ];

                if (!empty($respostasSalvas['_email_coletado'])) {
                    $linha['E-mail Coletado'] = $respostasSalvas['_email_coletado'];
                }

                $colunasUsadas = [];

                foreach ($campos as $campo) {
                    $labelLimpo = trim(strip_tags((string) $campo->label)) ?: $campo->name;
                    if (isset($colunasUsadas[$labelLimpo])) {
                        $colunasUsadas[$labelLimpo]++;
                        $labelColuna = "{$labelLimpo} ({$colunasUsadas[$labelLimpo]})";
                    } else {
                        $colunasUsadas[$labelLimpo] = 1;
                        $labelColuna = $labelLimpo;
                    }

                    $val = $respostasSalvas[$campo->name] ?? null;

                    if ($campo->tipo === 'system' && !empty($val)) {
                        if ($campo->subtipo === 'unidade') {
                            $val = Unidade::find($val)?->nome ?? $val;
                        } elseif ($campo->subtipo === 'curso') {
                            $val = Curso::find($val)?->nome ?? $val;
                        } elseif ($campo->subtipo === 'turno') {
                            $val = Turno::find($val)?->nome ?? $val;
                        }
                    }

                    $linha[$labelColuna] = $this->formatarValorParaExportacao($val);
                }

                if ($isCsv) {
                    if (!$cabecalhosEscritos) {
                        fputcsv($file, array_keys($linha), ';');
                        $cabecalhosEscritos = true;
                    }
                    $linhaSanitizada = array_map(function ($valor) {
                        $valorStr = (string) $valor;
                        if (preg_match('/^[\=\+\-\@\t\r]/', $valorStr)) {
                            return "'" . $valorStr;
                        }
                        return $valorStr;
                    }, $linha);

                    fputcsv($file, $linhaSanitizada, ';');
                } else {
                    $writer->addRow($linha);
                }

                $linhasProcessadas++;
                if ($linhasProcessadas % 50 === 0) {
                    $tracking->update(['linhas_processadas' => $linhasProcessadas]);
                }
            }

            if ($isCsv && $file) {
                fclose($file);
            } elseif ($writer) {
                $writer->close();
            }

            $tracking->update([
                'status'                 => 'concluido',
                'linhas_processadas'     => $linhasProcessadas,
                'arquivo_gerado_caminho' => $caminhoRelativo,
                'finished_at'            => now(),
            ]);
        } catch (\Throwable $e) {
            $tracking->update([
                'status'        => 'erro',
                'erro_mensagem' => json_encode([
                    ['linha' => 0, 'tipo' => 'Erro na Exportação', 'mensagem' => $e->getMessage()]
                ], JSON_UNESCAPED_UNICODE),
                'finished_at'   => now(),
            ]);
        }
    }

    private function formatarValorParaExportacao($val): string
    {
        if (is_array($val)) {
            $itens = [];
            foreach ($val as $k => $item) {
                if ($item === null || $item === '') continue;
                $formatado = $this->formatarValorParaExportacao($item);
                $itens[] = is_string($k) && !is_numeric($k) ? "{$k}: {$formatado}" : $formatado;
            }
            return empty($itens) ? '-' : implode(' | ', $itens);
        }

        $valStr = trim((string) $val);
        if ($valStr === '' || $valStr === '-') {
            return '-';
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
            $caminhos = preg_split('/[\|,]+/', $valStr);
            $urls = array_map(fn($p) => asset('storage/' . ltrim(trim($p), '/')), array_filter($caminhos));
            return implode(' | ', $urls);
        }

        return $valStr;
    }
}