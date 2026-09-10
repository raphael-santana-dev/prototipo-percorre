<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use App\Models\Inscricao;
use App\Models\Importacao;

class ExportarInscricoesFiltradasJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $trackingId;
    public $filtros;

    public function __construct($trackingId, $filtros)
    {
        $this->trackingId = $trackingId;
        $this->filtros = $filtros;
    }

    public function handle(): void
    {
        $tracking = Importacao::find($this->trackingId);
        if (!$tracking) return;

        $tracking->update(['status' => 'processando']);

        // 1. Aplica todos os filtros da tela
        $query = Inscricao::with(['curso', 'unidade', 'turno', 'ciclo', 'statusInscricao']);
        
        if (!empty($this->filtros['nome'])) {
            $query->where(function($q) {
                $q->where('nome', 'ilike', '%' . $this->filtros['nome'] . '%')
                  ->orWhere('cpf', 'like', '%' . $this->filtros['nome'] . '%');
            });
        }
        if (!empty($this->filtros['status'])) $query->where('status_inscricao_id', $this->filtros['status']);
        if (!empty($this->filtros['unidade'])) $query->where('unidade_id', $this->filtros['unidade']);
        if (!empty($this->filtros['turno'])) $query->where('turno_id', $this->filtros['turno']);
        if (!empty($this->filtros['curso'])) $query->where('curso_id', $this->filtros['curso']);
        if (!empty($this->filtros['ciclo'])) $query->where('ciclo_id', $this->filtros['ciclo']);
        if (!empty($this->filtros['etapa'])) {
            if ($this->filtros['etapa'] === 'Finalizado') $query->where('etapa_atual', 99);
            else $query->where('etapa_atual', $this->filtros['etapa']);
        }

        $inscricoes = $query->get();

        // 2. Monta o Mapa de Cabeçalhos (Incluindo os Campos Dinâmicos do Form)
        $chavesDinamicas = [];
        foreach ($inscricoes as $insc) {
            $dinamicos = is_string($insc->dados_dinamicos) ? json_decode($insc->dados_dinamicos, true) : ($insc->dados_dinamicos ?? []);
            foreach ($dinamicos as $key => $val) {
                if (str_contains(strtolower($key), 'form_config')) continue;
                
                if (is_array($val) && count(array_filter(array_keys($val), 'is_string')) > 0) {
                    foreach ($val as $rede => $user) {
                        $chavesDinamicas["{$key}.{$rede}"] = ucwords(str_replace('_', ' ', $key)) . " (" . ucfirst($rede) . ")";
                    }
                } else {
                    $chavesDinamicas[$key] = ucwords(str_replace('_', ' ', $key));
                }
            }
        }

        // 3. Prepara o Arquivo usando Spatie SimpleExcel
        $fileName = 'exportacao_inscricoes_' . time() . '.' . $tracking->formato;
        $caminhoRelativo = 'exportacoes/' . $fileName;
        Storage::disk('public')->makeDirectory('exportacoes');
        $caminhoAbsoluto = Storage::disk('public')->path($caminhoRelativo);

        $writer = \Spatie\SimpleExcel\SimpleExcelWriter::create($caminhoAbsoluto);
        if ($tracking->formato === 'csv') {
            $writer->useDelimiter(';');
        }

        $linhasProcessadas = 0;

        foreach ($inscricoes as $insc) {
            $etapaExibicao = $insc->etapa_atual == 99 ? 'Finalizado' : ($insc->etapa_atual == 100 ? 'Em Espera' : 'Passo ' . $insc->etapa_atual);
            
            // Array base (Spatie vai usar as "chaves" como Nome da Coluna)
            $linha = [
                'ID' => $insc->id,
                'Ciclo' => $insc->ciclo->nome ?? '-',
                'Etapa Atual' => $etapaExibicao,
                'Status' => $insc->statusInscricao->nome ?? 'Pendente',
                'Candidato' => $insc->nome,
                'CPF' => $insc->cpf,
                'E-mail' => $insc->email,
                'Celular / Zap' => $insc->celular,
                'Data Nascimento' => $insc->data_nascimento ? \Carbon\Carbon::parse($insc->data_nascimento)->format('d/m/Y') : '-',
                'Gênero/Sexo' => $insc->qual_o_sexo_atribuido_a_voce_no_seu_nascimento ?? $insc->genero ?? '-',
                'PcD' => $insc->possui_deficiencia === 'sim' ? 'Sim (' . $insc->natureza_deficiencia . ')' : 'Não',
                'Unidade' => $insc->unidade->nome ?? '-',
                'Curso' => $insc->curso->nome ?? '-',
                'Turno' => $insc->turno->nome ?? '-',
                'CEP' => $insc->cep,
                'Endereço Completo' => ($insc->logradouro ?? '') . ', ' . ($insc->numero ?? 'S/N') . ($insc->complemento ? ' - ' . $insc->complemento : ''),
                'Bairro' => $insc->bairro,
                'Cidade' => $insc->cidade,
                'Estado' => $insc->estado,
                'Score (Pontos)' => $insc->pontuacao_total ?? 0,
            ];

            // Injeta as colunas dinâmicas capturadas
            $dinamicos = is_string($insc->dados_dinamicos) ? json_decode($insc->dados_dinamicos, true) : ($insc->dados_dinamicos ?? []);
            
            foreach ($chavesDinamicas as $chaveInterna => $label) {
                if (str_contains($chaveInterna, '.')) {
                    [$parent, $child] = explode('.', $chaveInterna, 2);
                    $valor = $dinamicos[$parent][$child] ?? '';
                } else {
                    $valor = $dinamicos[$chaveInterna] ?? '';
                }
                
                $linha[$label] = is_array($valor) ? implode(', ', $valor) : $valor;
            }

            $writer->addRow($linha);

            $linhasProcessadas++;
            if ($linhasProcessadas % 50 === 0) {
                $tracking->update(['linhas_processadas' => $linhasProcessadas]);
            }
        }

        $tracking->update([
            'status' => 'concluido',
            'linhas_processadas' => $linhasProcessadas,
            'arquivo_gerado_caminho' => $caminhoRelativo
        ]);
    }
}