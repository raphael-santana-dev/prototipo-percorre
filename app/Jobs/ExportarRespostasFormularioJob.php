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
use Illuminate\Support\Str;
use Spatie\SimpleExcel\SimpleExcelWriter;

class ExportarRespostasFormularioJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;
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
        $tracking = SystemTask::find($this->trackingId);
        if (!$tracking) return;

        $tracking->update(['status' => 'processando']);
        $formulario = Formulario::find($this->formularioId);
        
        $campos = CampoFormulario::where('formulario_id', $this->formularioId)
            ->whereNotIn('tipo', ['config', 'html', 'divider', 'media', 'social', 'spacer', 'link'])
            ->orderBy('etapa')
            ->orderBy('ordem')
            ->get();

        $query = RespostaFormulario::where('formulario_id', $this->formularioId);

        if (!empty($this->filtros['ids']) && is_array($this->filtros['ids'])) {
            $query->whereIn('id', $this->filtros['ids']);
        }

        if (!empty($this->filtros['search'])) {
            $search = $this->filtros['search'];
            $query->where(function($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhere('respostas', 'ilike', "%{$search}%");
            });
        }
        if (!empty($this->filtros['data_inicio'])) {
            $query->where('created_at', '>=', $this->filtros['data_inicio'] . ' 00:00:00');
        }
        if (!empty($this->filtros['data_fim'])) {
            $query->where('created_at', '<=', $this->filtros['data_fim'] . ' 23:59:59');
        }
        if (!empty($this->filtros['filtro_curso'])) {
            $curso = \App\Models\Curso::find($this->filtros['filtro_curso']);
            if ($curso) $query->where('respostas', 'ilike', "%{$curso->nome}%");
        }
        if (!empty($this->filtros['filtro_unidade'])) {
            $unidade = \App\Modules\Unidade\Domain\Models\Unidade::find($this->filtros['filtro_unidade']);
            if ($unidade) $query->where('respostas', 'ilike', "%{$unidade->nome}%");
        }
        if (!empty($this->filtros['filtro_turno'])) {
            $turno = \App\Modules\Turno\Domain\Models\Turno::find($this->filtros['filtro_turno']);
            if ($turno) $query->where('respostas', 'ilike', "%{$turno->nome}%");
        }

        $sortField = $this->filtros['sortField'] ?? 'created_at';
        $sortDirection = $this->filtros['sortDirection'] ?? 'desc';
        $query->orderBy($sortField, $sortDirection);

        $respostas = $query->get();

        $fileName = 'respostas_' . Str::slug($formulario->titulo ?? 'formulario') . '_' . time() . '.' . $tracking->formato;
        $caminhoRelativo = 'exportacoes/' . $fileName;
        Storage::disk('public')->makeDirectory('exportacoes');
        $caminhoAbsoluto = Storage::disk('public')->path($caminhoRelativo);

        $writer = SimpleExcelWriter::create($caminhoAbsoluto);
        if ($tracking->formato === 'csv') $writer->useDelimiter(';');

        $linhasProcessadas = 0;

        foreach ($respostas as $resp) {
            $linha = [
                'Protocolo (ID)' => $resp->id,
                'Data de Envio' => $resp->created_at->format('d/m/Y H:i:s'),
            ];

            $respostasSalvas = is_string($resp->respostas) ? json_decode($resp->respostas, true) : ($resp->respostas ?? []);

            foreach ($campos as $campo) {
                $val = $respostasSalvas[$campo->name] ?? null;
                $linha[$campo->label] = $this->formatarValorParaExportacao($val);
            }

            $writer->addRow($linha);
            $linhasProcessadas++;
            if ($linhasProcessadas % 50 === 0) $tracking->update(['linhas_processadas' => $linhasProcessadas]);
        }

        $tracking->update([
            'status' => 'concluido',
            'linhas_processadas' => $linhasProcessadas,
            'arquivo_gerado_caminho' => $caminhoRelativo
        ]);
    }

    private function formatarValorParaExportacao($val): string
    {
        if (is_array($val)) {
            $itens = array_map(fn($item) => $this->formatarValorParaExportacao($item), array_filter($val, fn($v) => $v !== null && $v !== ''));
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
            $caminhos = explode('|', $valStr);
            $urls = array_map(fn($p) => asset('storage/' . ltrim(trim($p), '/')), array_filter($caminhos));
            return implode(' | ', $urls);
        }

        return $valStr;
    }
}