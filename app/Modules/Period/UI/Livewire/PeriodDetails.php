<?php

namespace App\Modules\Period\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use App\Models\Ciclo;
use App\Helpers\BreadcrumbHelper;

#[Layout('components.layouts.app')]
#[Title('Detalhes do Ciclo - Administrativo')]
class PeriodDetails extends Component
{
    use WithPagination;

    public Ciclo $ciclo;
    
    // Campo para buscar inscrições específicas
    public string $search = '';
    public array $breadcrumbs = [];

    public function mount(int $id)
    {
        abort_if(!auth()->user()->hasRole('dev|admin'), 403);

        $this->breadcrumbs = BreadcrumbHelper::generate();
        
        // Eager load apenas dos cursos, as inscrições deixamos para o render (para paginar)
        $this->ciclo = Ciclo::with('cursos')->findOrFail($id);
    }

    // Reseta a paginação ao digitar na busca
    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function recalcularPontuacoes()
    {
        // Trava de segurança: Apenas Devs ou Admins podem rodar isso
        abort_if(!auth()->user()->hasRole('dev|admin'), 403);

        $regras = is_string($this->ciclo->regras_pontuacao) 
            ? json_decode($this->ciclo->regras_pontuacao, true) 
            : ($this->ciclo->regras_pontuacao ?? []);

        if (empty($regras)) {
            session()->flash('erro', 'Este ciclo não possui regras de pontuação cadastradas para o recálculo.');
            return;
        }

        // Pega todas as inscrições finalizadas (etapa >= total_etapas ou status diferente de lead/incompleto)
        // Para simplificar, vamos recalcular de todas as inscrições atreladas ao ciclo
        $inscricoes = $this->ciclo->inscricoes()->get();
        $atualizados = 0;

        foreach ($inscricoes as $inscricao) {
            $total = 0;
            $detalhes = ['auditoria_detalhada' => []];

            $respostas = is_string($inscricao->dados_dinamicos) 
                ? json_decode($inscricao->dados_dinamicos, true) 
                : ($inscricao->dados_dinamicos ?? []);

            foreach ($regras as $regra) {
                $campo = $regra['campo'];
                $valorResposta = null;

                // 1. Mapeamento dos campos (Fixo x Dinâmico)
                if ($campo === 'idade' && $inscricao->data_nascimento) {
                    $valorResposta = \Carbon\Carbon::parse($inscricao->data_nascimento)->age;
                } elseif (in_array($campo, ['estado', 'cidade', 'curso_id', 'turno_id', 'possui_deficiencia'])) {
                    $valorResposta = $inscricao->$campo;
                } elseif (isset($respostas[$campo])) {
                    $valorResposta = $respostas[$campo];
                }

                // 2. Lógica Matemática de Validação
                if ($valorResposta !== null && $valorResposta !== '') {
                    $valorAlvoStr = $regra['valor'];
                    if (in_array($regra['operador'], ['between', 'in'])) {
                        $valoresEsperados = array_map('trim', explode(',', $valorAlvoStr));
                    } else {
                        $valoresEsperados = [trim($valorAlvoStr)];
                    }

                    $valorAlvo = $valoresEsperados[0] ?? null;
                    $pontuou = false;

                    switch ($regra['operador']) {
                        case '=': $pontuou = (strtolower(trim((string)$valorResposta)) === strtolower(trim((string)$valorAlvo))); break;
                        case '!=': $pontuou = (strtolower(trim((string)$valorResposta)) !== strtolower(trim((string)$valorAlvo))); break;
                        case '>=': $pontuou = ((float)$valorResposta >= (float)$valorAlvo); break;
                        case '<=': $pontuou = ((float)$valorResposta <= (float)$valorAlvo); break;
                        case 'between':
                            $min = (float)($valoresEsperados[0] ?? 0);
                            $max = (float)($valoresEsperados[1] ?? $min);
                            $pontuou = ((float)$valorResposta >= $min && (float)$valorResposta <= $max);
                            break;
                        case 'in':
                            $respostasValidas = array_map(fn($v) => strtolower(trim((string)$v)), $valoresEsperados);
                            $pontuou = in_array(strtolower(trim((string)$valorResposta)), $respostasValidas);
                            break;
                    }

                    if ($pontuou) {
                        $total += (int) $regra['pontos'];
                        $detalhes['auditoria_detalhada'][] = [
                            'campo_avaliado' => $campo,
                            'resposta_dada' => $valorResposta,
                            'pontos_ganhos' => (int) $regra['pontos'],
                            'condicao' => "{$regra['operador']} " . implode(', ', $valoresEsperados)
                        ];
                    }
                }
            }

            if ($total > 0) {
                $detalhes['motivo_auditoria'] = "Recálculo em Massa pelo Administrador. Total: {$total} pts.";
            } else {
                $detalhes = null;
            }

            // Atualiza direto no banco silenciosamente
            $inscricao->update([
                'pontuacao_total' => $total,
                'pontuacao_detalhes' => $detalhes
            ]);
            
            $atualizados++;
        }

        session()->flash('sucesso', "Recálculo finalizado! {$atualizados} inscrições tiveram seus scores atualizados.");
    }

    public function render()
    {
        // Busca as inscrições deste ciclo com filtros
        $inscricoes = $this->ciclo->inscricoes()
            ->when($this->search, function ($query) {
                $query->where(function($q) {
                    $q->where('nome', 'ilike', '%' . $this->search . '%') // ilike é ótimo para PostgreSQL
                      ->orWhere('cpf', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'ilike', '%' . $this->search . '%');
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('livewire.period.period-details', [
            'inscricoes' => $inscricoes
        ]);
    }
}