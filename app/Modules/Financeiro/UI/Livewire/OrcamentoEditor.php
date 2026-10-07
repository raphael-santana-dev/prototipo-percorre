<?php

namespace App\Modules\Financeiro\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Modules\Financeiro\Domain\Models\Orcamento;
use App\Modules\Financeiro\Domain\Models\OrcamentoItem;
use App\Modules\Financeiro\Domain\Models\Natureza;
use App\Modules\Financeiro\Domain\Models\OrcamentoAvaliacao; // Adicionado para os logs

#[Layout('components.layouts.app')]
#[Title('Edição de Orçamento - Excel View')]
class OrcamentoEditor extends Component
{
    public Orcamento $orcamento;
    public string $justificativaGeral = '';
    public array $itensOrcamento = [];
    public array $itensRemovidos = [];
    
    public bool $modalNovaNaturezaAberto = false;
    public string $novaNaturezaDescricao = '';

    // --- VARIÁVEIS DE DESBLOQUEIO (DUPLO CLIQUE) ---
    public bool $modalDesbloqueioAberto = false;
    public ?int $linhaParaDesbloquear = null;
    public string $justificativaDesbloqueio = '';

    public int $anoSimulacao = 2026;
    public bool $isLockedGlobal = false;

    public function mount($id)
    {
        $this->orcamento = Orcamento::with(['itens.natureza', 'itens.avaliacoes', 'centroCusto'])->findOrFail($id);
        
        if (!auth()->user()->hasRole('dev|admin') && !auth()->user()->can('financeiro.orcamentos.global')) {
            if (method_exists(auth()->user(), 'centros_de_custo')) {
                $ccPermitidos = auth()->user()->centros_de_custo->pluck('codigo')->toArray();
                abort_unless(in_array($this->orcamento->ccusto, $ccPermitidos), 403, 'Sem permissão para este Centro de Custo.');
            } else {
                abort(403);
            }
        }

        $this->anoSimulacao = session('ano_simulacao_orcamento', date('Y'));
        
        $this->isLockedGlobal = in_array($this->orcamento->status, ['Aprovado', 'Aprovado com ressalvas', 'Finalizado']);
        if (!auth()->user()->hasRole('dev|admin') && $this->orcamento->status === 'Finalizado') {
            $this->isLockedGlobal = true;
        }

        $this->justificativaGeral = $this->orcamento->descricao_despesa ?? '';
        $this->carregarItens();
    }

    private function carregarItens()
    {
        $this->itensOrcamento = [];
        foreach ($this->orcamento->itens as $item) {
            $this->itensOrcamento[] = [
                'id' => $item->id, 'natureza_codigo' => $item->natureza_codigo, 'descricao' => $item->descricao,
                'status' => $item->status, 'avaliacoes' => $item->avaliacoes,
                'previsto_jan' => $item->previsto_jan, 'valor_jan' => $item->valor_jan,
                'previsto_fev' => $item->previsto_fev, 'valor_fev' => $item->valor_fev,
                'previsto_mar' => $item->previsto_mar, 'valor_mar' => $item->valor_mar,
                'previsto_abr' => $item->previsto_abr, 'valor_abr' => $item->valor_abr,
                'previsto_mai' => $item->previsto_mai, 'valor_mai' => $item->valor_mai,
                'previsto_jun' => $item->previsto_jun, 'valor_jun' => $item->valor_jun,
                'previsto_jul' => $item->previsto_jul, 'valor_jul' => $item->valor_jul,
                'previsto_ago' => $item->previsto_ago, 'valor_ago' => $item->valor_ago,
                'previsto_set' => $item->previsto_set, 'valor_set' => $item->valor_set,
                'previsto_out' => $item->previsto_out, 'valor_out' => $item->valor_out,
                'previsto_nov' => $item->previsto_nov, 'valor_nov' => $item->valor_nov,
                'previsto_dez' => $item->previsto_dez, 'valor_dez' => $item->valor_dez,
            ];
        }

        if (empty($this->itensOrcamento) && !$this->isLockedGlobal) {
            $this->adicionarItem();
        }
    }

    // --- MÉTODOS DE DESBLOQUEIO DE LINHA ---
    public function solicitarDesbloqueio($index)
    {
        // Se a linha não for 'Aprovado' ou o orçamento estiver bloqueado globalmente, ignora
        if ($this->itensOrcamento[$index]['status'] !== 'Aprovado' || $this->isLockedGlobal) return;

        $this->linhaParaDesbloquear = $index;
        $this->justificativaDesbloqueio = '';
        $this->modalDesbloqueioAberto = true;
    }

    public function confirmarDesbloqueio()
    {
        $index = $this->linhaParaDesbloquear;
        
        if ($index !== null && isset($this->itensOrcamento[$index])) {
            // Desbloqueia na Interface (muda para Corrigido)
            $this->itensOrcamento[$index]['status'] = 'Corrigido';

            // Cria imediatamente o log de auditoria da alteração na base de dados
            if (!empty($this->itensOrcamento[$index]['id'])) {
                OrcamentoAvaliacao::create([
                    'orcamento_id' => $this->orcamento->id,
                    'orcamento_item_id' => $this->itensOrcamento[$index]['id'],
                    'user_id' => auth()->id(),
                    'user_nome' => auth()->user()->name,
                    'status_aplicado' => 'Edição Pós-Aprovação',
                    'comentario' => trim($this->justificativaDesbloqueio) ?: 'Edição desbloqueada pelo gestor.',
                    'tipo_evento' => 'edicao_item_aprovado'
                ]);
            }
            
            // Recarrega o orçamento silenciosamente para atualizar a Timeline
            $this->orcamento->refresh();

            $this->dispatch('sucesso', msg: 'Linha desbloqueada! Os campos estão agora abertos para edição.');
        }
        
        $this->modalDesbloqueioAberto = false;
        $this->linhaParaDesbloquear = null;
    }
    // ----------------------------------------

    public function adicionarItem() {
        $this->itensOrcamento[] = [
            'id' => null, 'natureza_codigo' => '', 'descricao' => '', 'status' => 'Criado', 'avaliacoes' => [],
            'previsto_jan' => 0, 'valor_jan' => 0, 'previsto_fev' => 0, 'valor_fev' => 0,
            'previsto_mar' => 0, 'valor_mar' => 0, 'previsto_abr' => 0, 'valor_abr' => 0,
            'previsto_mai' => 0, 'valor_mai' => 0, 'previsto_jun' => 0, 'valor_jun' => 0,
            'previsto_jul' => 0, 'valor_jul' => 0, 'previsto_ago' => 0, 'valor_ago' => 0,
            'previsto_set' => 0, 'valor_set' => 0, 'previsto_out' => 0, 'valor_out' => 0,
            'previsto_nov' => 0, 'valor_nov' => 0, 'previsto_dez' => 0, 'valor_dez' => 0,
        ];
    }

    public function removerItem($index) {
        if (!empty($this->itensOrcamento[$index]['id'])) $this->itensRemovidos[] = $this->itensOrcamento[$index]['id'];
        unset($this->itensOrcamento[$index]);
        $this->itensOrcamento = array_values($this->itensOrcamento); 
        if (empty($this->itensOrcamento)) $this->adicionarItem(); 
    }

    public function abrirModalNovaNatureza()
    {
        $this->novaNaturezaDescricao = '';
        $this->modalNovaNaturezaAberto = true;
    }

    public function salvarNovaNatureza()
    {
        $this->validate(['novaNaturezaDescricao' => 'required|string|min:3|max:255']);

        $codigoAleatorio = 'D' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        while (Natureza::where('codigo', $codigoAleatorio)->exists()) {
            $codigoAleatorio = 'D' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        }

        Natureza::create(['codigo' => $codigoAleatorio, 'descricao' => trim($this->novaNaturezaDescricao), 'disponivel_orcamento' => true]);

        $this->modalNovaNaturezaAberto = false;
        $this->dispatch('sucesso', msg: "Natureza {$codigoAleatorio} cadastrada com sucesso!");
    }

    public function salvarOrcamento() { $this->processarGravacao('Em elaboração', 'Planilha guardada como rascunho com sucesso!'); }
    public function finalizarOrcamento() { $this->processarGravacao('Finalizado', 'Orçamento submetido para aprovação da Diretoria!'); }

    private function processarGravacao($statusDesejado, $mensagemSucesso) {
        if ($this->isLockedGlobal) {
            $this->dispatch('erro', msg: 'Ação bloqueada. Orçamento finalizado ou aprovado.'); return;
        }

        foreach ($this->itensOrcamento as $item) {
            if (empty(trim($item['natureza_codigo']))) { $this->dispatch('erro', msg: 'Todas as linhas devem ter uma Natureza válida selecionada.'); return; }
        }

        $novoStatus = ($statusDesejado === 'Em elaboração' && !in_array($this->orcamento->status, ['Criado', 'Reprovado'])) ? $this->orcamento->status : $statusDesejado;
        $this->orcamento->update(['descricao_despesa' => $this->justificativaGeral, 'status' => $novoStatus]);
        
        if (!empty($this->itensRemovidos)) OrcamentoItem::whereIn('id', $this->itensRemovidos)->delete();

        foreach ($this->itensOrcamento as $dataItem) {
            $descricao = trim($dataItem['descricao']);
            if (empty($dataItem['id'])) {
                $naturezaDB = Natureza::where('codigo', $dataItem['natureza_codigo'])->first();
                $descricao = $naturezaDB ? $naturezaDB->descricao : $descricao;
            }

            $statusItem = (isset($dataItem['status']) && in_array($dataItem['status'], ['Reprovado', 'Aprovado com ressalvas'])) ? 'Corrigido' : ($dataItem['status'] ?? 'Criado');

            OrcamentoItem::updateOrCreate(
                ['id' => $dataItem['id'], 'orcamento_id' => $this->orcamento->id],
                [
                    'natureza_codigo' => trim($dataItem['natureza_codigo']), 'descricao' => $descricao, 'status' => $statusItem,
                    'previsto_jan' => empty($dataItem['previsto_jan']) ? 0 : (float) $dataItem['previsto_jan'], 'valor_jan' => empty($dataItem['valor_jan']) ? 0 : (float) $dataItem['valor_jan'],
                    'previsto_fev' => empty($dataItem['previsto_fev']) ? 0 : (float) $dataItem['previsto_fev'], 'valor_fev' => empty($dataItem['valor_fev']) ? 0 : (float) $dataItem['valor_fev'],
                    'previsto_mar' => empty($dataItem['previsto_mar']) ? 0 : (float) $dataItem['previsto_mar'], 'valor_mar' => empty($dataItem['valor_mar']) ? 0 : (float) $dataItem['valor_mar'],
                    'previsto_abr' => empty($dataItem['previsto_abr']) ? 0 : (float) $dataItem['previsto_abr'], 'valor_abr' => empty($dataItem['valor_abr']) ? 0 : (float) $dataItem['valor_abr'],
                    'previsto_mai' => empty($dataItem['previsto_mai']) ? 0 : (float) $dataItem['previsto_mai'], 'valor_mai' => empty($dataItem['valor_mai']) ? 0 : (float) $dataItem['valor_mai'],
                    'previsto_jun' => empty($dataItem['previsto_jun']) ? 0 : (float) $dataItem['previsto_jun'], 'valor_jun' => empty($dataItem['valor_jun']) ? 0 : (float) $dataItem['valor_jun'],
                    'previsto_jul' => empty($dataItem['previsto_jul']) ? 0 : (float) $dataItem['previsto_jul'], 'valor_jul' => empty($dataItem['valor_jul']) ? 0 : (float) $dataItem['valor_jul'],
                    'previsto_ago' => empty($dataItem['previsto_ago']) ? 0 : (float) $dataItem['previsto_ago'], 'valor_ago' => empty($dataItem['valor_ago']) ? 0 : (float) $dataItem['valor_ago'],
                    'previsto_set' => empty($dataItem['previsto_set']) ? 0 : (float) $dataItem['previsto_set'], 'valor_set' => empty($dataItem['valor_set']) ? 0 : (float) $dataItem['valor_set'],
                    'previsto_out' => empty($dataItem['previsto_out']) ? 0 : (float) $dataItem['previsto_out'], 'valor_out' => empty($dataItem['valor_out']) ? 0 : (float) $dataItem['valor_out'],
                    'previsto_nov' => empty($dataItem['previsto_nov']) ? 0 : (float) $dataItem['previsto_nov'], 'valor_nov' => empty($dataItem['valor_nov']) ? 0 : (float) $dataItem['valor_nov'],
                    'previsto_dez' => empty($dataItem['previsto_dez']) ? 0 : (float) $dataItem['previsto_dez'], 'valor_dez' => empty($dataItem['valor_dez']) ? 0 : (float) $dataItem['valor_dez'],
                ]
            );
        }

        $this->dispatch('sucesso', msg: $mensagemSucesso);
        
        if ($statusDesejado === 'Finalizado') {
            return redirect()->route('financeiro.orcamentos');
        }
        
        $this->orcamento->refresh();
        $this->carregarItens();
    }

    public function voltar() { return redirect()->route('financeiro.orcamentos'); }

    public function render()
    {
        $todasNaturezas = Natureza::where('disponivel_orcamento', true)->orderBy('descricao')->get();
        return view('livewire.financeiro.orcamento-editor', ['todasNaturezas' => $todasNaturezas]);
    }
}