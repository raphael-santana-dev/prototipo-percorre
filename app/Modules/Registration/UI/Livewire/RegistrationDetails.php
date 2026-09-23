<?php

namespace App\Modules\Registration\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Inscricao;
use App\Models\StatusInscricao;

#[Layout('components.layouts.app')]
#[Title('Detalhes da Inscrição')]
class RegistrationDetails extends Component
{
    public Inscricao $inscricao;
    public $status_selecionado; 
    public bool $modalAntiSpamAberto = false;

    public function mount($id)
    {
        abort_if(!feature('inscricao.visualizar'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('inscricao.visualizar'), 403);

        $this->inscricao = Inscricao::with(['unidade', 'curso', 'turno', 'ciclo'])->findOrFail($id);
        $this->status_selecionado = $this->inscricao->status_inscricao_id;
    }

    public function getDataInscricao()
    {
        $dinamicos = is_string($this->inscricao->dados_dinamicos) ? json_decode($this->inscricao->dados_dinamicos, true) : ($this->inscricao->dados_dinamicos ?? []);
        $dataRaw = $dinamicos['Submission started'] ?? $dinamicos['submission started'] ?? $dinamicos['Submission Started'] ?? $this->inscricao->created_at;
        
        try {
            return \Carbon\Carbon::parse($dataRaw);
        } catch (\Exception $e) {
            return clone $this->inscricao->created_at;
        }
    }

    public function getDataAtualizacao()
    {
        $dinamicos = is_string($this->inscricao->dados_dinamicos) ? json_decode($this->inscricao->dados_dinamicos, true) : ($this->inscricao->dados_dinamicos ?? []);
        $dataRaw = $dinamicos['Last updated'] ?? $dinamicos['last updated'] ?? $dinamicos['Last Updated'] ?? $this->inscricao->updated_at;
        
        try {
            return \Carbon\Carbon::parse($dataRaw);
        } catch (\Exception $e) {
            return clone $this->inscricao->updated_at;
        }
    }

    public function atualizarStatus()
    {
        abort_if(!feature('inscricao.editar'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('inscricao.editar'), 403);

        if ($this->inscricao->status_inscricao_id == $this->status_selecionado) {
            $this->dispatch('erro', msg: 'O candidato já se encontra neste status!');
            return;
        }

        $statusNovo = StatusInscricao::find($this->status_selecionado);
        if (!$statusNovo) return;

        $eventoGatilho = 'inscricao.status.' . \Illuminate\Support\Str::slug($statusNovo->nome, '_');
        $automacao = \App\Modules\Comunicacao\Domain\Models\Automacao::where('evento_gatilho', $eventoGatilho)->where('status', true)->first();

        if ($automacao) {
            $jaRecebeu = \App\Modules\Comunicacao\Domain\Models\Comunicado::where('template_id', $automacao->template_id)
                ->where('inscricao_id', $this->inscricao->id)
                ->exists();

            if ($jaRecebeu) {
                $this->modalAntiSpamAberto = true;
                return; 
            }
        }

        $this->executarMudancaStatusFinal();
    }

    public function cancelarAntiSpam() 
    {
        $this->modalAntiSpamAberto = false;
        $this->status_selecionado = $this->inscricao->status_inscricao_id; 
    }

    public function executarMudancaStatusFinal()
    {
        $statusNovo = StatusInscricao::find($this->status_selecionado);
        
        $this->inscricao->status_inscricao_id = $statusNovo->id;
        $this->inscricao->save();

        $eventoGatilho = 'inscricao.status.' . \Illuminate\Support\Str::slug($statusNovo->nome, '_');
        \App\Modules\Comunicacao\Services\AutomacaoService::disparar($eventoGatilho, $this->inscricao);

        $this->inscricao->refresh(); 
        $this->modalAntiSpamAberto = false;
        $this->dispatch('sucesso', msg: 'Status atualizado com sucesso!');
    }

    public function abrirRegras()
    {
        $regrasRaw = is_string($this->inscricao->ciclo->regras_pontuacao)
            ? json_decode($this->inscricao->ciclo->regras_pontuacao, true)
            : ($this->inscricao->ciclo->regras_pontuacao ?? []);

        $detalhes = is_string($this->inscricao->pontuacao_detalhes)
            ? json_decode($this->inscricao->pontuacao_detalhes, true)
            : ($this->inscricao->pontuacao_detalhes ?? []);

        $auditoria = $detalhes['auditoria_detalhada'] ?? [];

        $agrupadas = collect($regrasRaw)->groupBy(function($r) {
            return $r['campo'] ?? 'Regra Global';
        });

        $html = '<div class="space-y-6">';
        foreach ($agrupadas as $campo => $regras) {
            $nomeCampoFormatado = str_replace('_', ' ', strtoupper($campo));
            $html .= "<div class='bg-gray-50 dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 shadow-sm'>";
            $html .= "<h4 class='text-xs font-bold text-gray-500 mb-3 tracking-wider flex items-center gap-2'><i class='ph-fill ph-check-square-offset text-purpura-500'></i> {$nomeCampoFormatado}</h4>";
            $html .= "<div class='space-y-2'>";

            foreach ($regras as $regra) {
                $tipo = $regra['tipo_regra'] ?? 'padrao';
                $pontos = $regra['pontos'] ?? 0;
                $operador = $regra['operador'] ?? '=';
                $valor = $regra['valor'] ?? '';

                $valores = array_map('trim', explode(',', (string)$valor));
                $condicaoStr = match ($operador) {
                    '=' => "Igual a '{$valor}'",
                    '!=' => "Diferente de '{$valor}'",
                    '>=' => "Maior ou igual a {$valor}",
                    '<=' => "Menor ou igual a {$valor}",
                    '>' => "Maior que {$valor}",
                    '<' => "Menor que {$valor}",
                    'between' => "Entre " . ($valores[0] ?? '') . " e " . ($valores[1] ?? ''),
                    'in' => "Dentre: " . implode(' ou ', $valores),
                    default => "{$operador} {$valor}"
                };

                $pontosStr = $tipo === 'multiplicador_percentual' ? "+{$pontos}%" : "+{$pontos} pts";

                // NOVO MOTOR DE MATCH DE REGRAS: À prova de falhas com separadores
                $atendida = false;
                foreach ($auditoria as $aud) {
                    $campoAuditoria = strtolower(trim($aud['campo_avaliado'] ?? ''));
                    $campoAtual = strtolower(trim($campo));
                    
                    if ($campoAuditoria === $campoAtual || str_contains($campoAuditoria, $campoAtual) || str_contains($campoAtual, $campoAuditoria)) {
                        if ($aud['pontos_ganhos'] == $pontos) {
                            $condAudLimpa = str_replace('Exigência: ', '', $aud['condicao'] ?? '');
                            
                            // 1. Tenta correspondência exata ou contém a string formatada
                            if ($condAudLimpa === $condicaoStr || str_contains($condAudLimpa, $condicaoStr) || str_contains($condicaoStr, $condAudLimpa)) {
                                $atendida = true;
                                break;
                            }
                            
                            // 2. Se for 'between' ou 'in', procura as partes isoladas ('15' e '29')
                            if (in_array($operador, ['between', 'in'])) {
                                $allMatched = true;
                                foreach ($valores as $v) {
                                    if (!str_contains($condAudLimpa, trim($v))) {
                                        $allMatched = false;
                                        break;
                                    }
                                }
                                if ($allMatched) {
                                    $atendida = true;
                                    break;
                                }
                            }

                            // 3. Fallback cru
                            if (!empty($valor) && str_contains($condAudLimpa, (string)$valor)) {
                                $atendida = true;
                                break;
                            }
                        }
                    }
                }

                $bgClass = $atendida ? 'bg-green-50 border-green-200 dark:bg-green-900/30 dark:border-green-800' : 'bg-white border-gray-100 dark:bg-gray-700 dark:border-gray-600';
                $icon = $atendida ? '<i class="ph-fill ph-check-circle text-green-500 text-lg"></i>' : '<i class="ph ph-circle text-gray-300 text-lg"></i>';
                $textColor = $atendida ? 'text-green-800 dark:text-green-400' : 'text-gray-700 dark:text-gray-300';
                $ptsColor = $atendida ? 'text-green-700 bg-green-100 dark:bg-green-900/50 px-2 py-0.5 rounded' : 'text-gray-400';

                $html .= "<div class='flex items-center justify-between p-3 rounded-lg border {$bgClass}'>";
                $html .= "<div class='flex items-center gap-3'>";
                $html .= $icon;
                $html .= "<span class='block text-sm font-bold {$textColor}'>{$condicaoStr}</span>";
                $html .= "</div>";
                $html .= "<span class='font-black text-[10px] {$ptsColor}'>{$pontosStr}</span>";
                $html .= "</div>";
            }
            $html .= "</div></div>";
        }
        $html .= '</div>';

        if ($agrupadas->isEmpty()) {
            $html = '<div class="text-center py-8 text-gray-500"><i class="ph-fill ph-warning-circle text-4xl mb-2 text-gray-300"></i><p>Nenhuma regra configurada no ciclo.</p></div>';
        }

        $this->dispatch('load-quick-view', [
            'title' => 'Mapa de Regras do Ciclo',
            'subtitle' => 'Critérios avaliados para a pontuação',
            'icon' => 'ph-list-numbers',
            'maxWidth' => 'xl',
            'allowFullscreen' => true,
            'data' => [
                'Critérios e Acertos do Candidato' => $html
            ]
        ]);
    }

    public function render()
    {
        $todosStatus = StatusInscricao::orderBy('nome')->get();

        return view('livewire.registration.registration-details', [
            'todosStatus' => $todosStatus
        ]);
    }
}