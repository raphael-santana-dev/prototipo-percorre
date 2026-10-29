<?php

namespace App\Modules\Financeiro\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Mail\OrcamentoFinalizadoMail;
use App\Modules\Financeiro\Domain\Models\Orcamento;
use App\Modules\Financeiro\Domain\Models\OrcamentoItem;
use App\Modules\Financeiro\Domain\Models\Natureza;
use App\Modules\Financeiro\Domain\Models\OrcamentoAvaliacao;
use App\Modules\Financeiro\Services\ProtheusOrcamentoService;

#[Layout('components.layouts.app')]
#[Title('Edição de Orçamento')]
class OrcamentoEditor extends Component
{
    public Orcamento $orcamento;
    public string $justificativaGeral = '';
    public array $itensOrcamento = [];
    public array $itensRemovidos = [];

    // Seleção em lote (checkboxes)
    public array $itensSelecionados = [];
    public bool $selecionarTudo = false;

    // Índice do item atualmente focado no painel "Despesa selecionada"
    public int $itemSelecionadoIndex = 0;

    // Controle dos itens nos quais o usuário clicou nesta sessão
    public array $itensClicados = [];

    public bool $modalConfirmarEnvioAberto = false;

    // Histórico e Comentários divididos em Geral e por Item
    public bool $drawerComentariosAberto = false;
    public string $abaComentario = 'geral';
    public ?int $itemComentarioId = null;
    public string $novoComentarioGeral = '';
    public string $novoComentarioItem = '';

    public bool $modalReaberturaAberto = false;
    public string $motivoReabertura = '';

    public bool $isLockedGlobal = false;

    public array $cenariosDisponiveis = [
        '1' => '1 - Conservador',
        '2' => '2 - Moderado',
        '3' => '3 - Aspiracional',
    ];

    public function mount($id)
    {
        $this->orcamento = Orcamento::with([
            'itens' => fn($q) => $q->orderBy('id', 'asc'),
            'itens.natureza',
            'itens.avaliacoes',
            'centroCusto',
            'logsGerais'
        ])->findOrFail($id);

        if ($this->orcamento->itens->isEmpty()) {
            \App\Modules\Financeiro\Services\ProtheusOrcamentoService::sincronizarDetalhesOrcamento($this->orcamento);
            $this->orcamento->load(['itens' => fn($q) => $q->orderBy('id', 'asc'), 'itens.natureza', 'itens.avaliacoes']);
        }
        
        // Verifica se o usuário tem acesso global para Edição (retorna null) ou lista de CCs permitidos para Edição
        $ccPermitidosEdicao = auth()->user()->obterCodigosCcEdicao();
        if ($ccPermitidosEdicao !== null) {
            abort_unless(
                in_array($this->orcamento->ccusto, $ccPermitidosEdicao, true),
                403,
                'Sem permissão para editar este Centro de Custo.'
            );
        }

        $this->atualizarBloqueioGlobal();

        $this->justificativaGeral = $this->orcamento->descricao_despesa ?? '';
        $this->carregarItens();

        if (!empty($this->itensOrcamento)) {
            $this->selecionarItem(0);
        }
    }

    private function atualizarBloqueioGlobal(): void
    {
        $statusBloqueados = [
            'Finalizado – Aguardando Aprovação',
            'Finalizado – Aprovado',
            'Finalizado – Integrado ao Protheus',
            'Finalizado',
            'Aprovado',
            'Aprovado com ressalvas',
        ];

        $this->isLockedGlobal = in_array($this->orcamento->status, $statusBloqueados, true);

        if ($this->orcamento->prazo_edicao && now()->greaterThan($this->orcamento->prazo_edicao)) {
            $this->isLockedGlobal = true;
        }
    }

    private function carregarItens(): void
    {
        $this->itensOrcamento = [];
        
        $itensOrdenados = $this->orcamento->itens()
            ->with(['natureza', 'avaliacoes'])
            ->orderBy('id', 'asc')
            ->get();

        $seq = 1;
        foreach ($itensOrdenados as $item) {
            $natObj = $item->natureza;
            $natNome = $natObj ? $natObj->descricao : ($item->natureza_codigo ? 'Natureza Importada' : '');
            $statusNormalizado = $item->status ?: 'Importado';
            if ($statusNormalizado === 'Criado') {
                $statusNormalizado = 'Importado';
            }

            $idDespesaFormatado = !empty($item->id_despesa)
                ? str_pad((string) $item->id_despesa, 3, '0', STR_PAD_LEFT)
                : str_pad((string) $seq, 3, '0', STR_PAD_LEFT);

            $this->itensOrcamento[] = [
                'temp_id'         => 'db_' . $item->id,
                'id'              => $item->id,
                'id_despesa'      => $idDespesaFormatado,
                'natureza_codigo' => (string) ($item->natureza_codigo ?? ''), 
                'natureza_nome'   => $natNome,
                'categoria'       => $natObj ? ($natObj->categoria ?? '') : '',
                'cenario'         => (string) ($item->cenario ?: '1'),
                'descricao'       => trim((string) $item->descricao),
                'status'          => $statusNormalizado, 
                'avaliacoes'      => $item->avaliacoes ? $item->avaliacoes->toArray() : [],
                'previsto_jan'    => (float) $item->previsto_jan, 'valor_jan' => (float) $item->valor_jan,
                'previsto_fev'    => (float) $item->previsto_fev, 'valor_fev' => (float) $item->valor_fev,
                'previsto_mar'    => (float) $item->previsto_mar, 'valor_mar' => (float) $item->valor_mar,
                'previsto_abr'    => (float) $item->previsto_abr, 'valor_abr' => (float) $item->valor_abr,
                'previsto_mai'    => (float) $item->previsto_mai, 'valor_mai' => (float) $item->valor_mai,
                'previsto_jun'    => (float) $item->previsto_jun, 'valor_jun' => (float) $item->valor_jun,
                'previsto_jul'    => (float) $item->previsto_jul, 'valor_jul' => (float) $item->valor_jul,
                'previsto_ago'    => (float) $item->previsto_ago, 'valor_ago' => (float) $item->valor_ago,
                'previsto_set'    => (float) $item->previsto_set, 'valor_set' => (float) $item->valor_set,
                'previsto_out'    => (float) $item->previsto_out, 'valor_out' => (float) $item->valor_out,
                'previsto_nov'    => (float) $item->previsto_nov, 'valor_nov' => (float) $item->valor_nov,
                'previsto_dez'    => (float) $item->previsto_dez, 'valor_dez' => (float) $item->valor_dez,
            ];
            $seq++;
        }

        if (empty($this->itensOrcamento) && !$this->isLockedGlobal) {
            $this->adicionarItem();
        }

        if ($this->itemSelecionadoIndex >= count($this->itensOrcamento)) {
            $this->itemSelecionadoIndex = max(0, count($this->itensOrcamento) - 1);
        }
    }

    private function encontrarIndicePorTempId(string $tempId): ?int
    {
        foreach ($this->itensOrcamento as $idx => $item) {
            if ((string) ($item['temp_id'] ?? '') === $tempId) {
                return $idx;
            }
        }
        return null;
    }

    public function itemPodeSerEditado(array $item): bool
    {
        if ($this->isLockedGlobal) {
            return false;
        }

        $statusItem = $item['status'] ?? 'Importado';

        // Quando o orçamento retorna para revisão pelo responsável, apenas os itens marcados para revisão/reprovados são editáveis
        if ($this->orcamento->status === 'Aprovação – Aguardando Revisão pelo Responsável') {
            return in_array($statusItem, [
                'Aguardando Revisão pelo Responsável',
                'Não Aprovada',
                'Não Aprovado',
                'Reprovado',
                'Reprovado parcialmente',
                'Em revisão/atualização'
            ], true);
        }

        if (in_array($statusItem, ['Aprovada', 'Aprovado', 'Aprovada Parcialmente'], true)) {
            return false;
        }

        return true;
    }

    public function selecionarItem(int $index): void
    {
        if (!isset($this->itensOrcamento[$index])) {
            return;
        }

        $this->itemSelecionadoIndex = $index;
        $tempId = (string) $this->itensOrcamento[$index]['temp_id'];

        if (!in_array($tempId, $this->itensClicados, true)) {
            $this->itensClicados[] = $tempId;
        }

        if (!empty($this->itensOrcamento[$index]['id'])) {
            $this->itemComentarioId = (int) $this->itensOrcamento[$index]['id'];
        }
    }

    /**
     * Atualiza o valor de um mês específico garantindo pelo $tempId que NUNCA altere outro item
     */
    public function atualizarValorMes(string $tempId, string $siglaMes, $novoValor): void
    {
        $mesesValidos = ['jan','fev','mar','abr','mai','jun','jul','ago','set','out','nov','dez'];
        if (!in_array($siglaMes, $mesesValidos, true)) {
            return;
        }

        $idx = $this->encontrarIndicePorTempId($tempId);
        if ($idx === null || !$this->itemPodeSerEditado($this->itensOrcamento[$idx])) {
            return;
        }

        $valorNormalizado = is_numeric($novoValor) ? max(0, round((float) $novoValor, 2)) : 0.0;
        $campoMes = "valor_{$siglaMes}";

        if ((float) ($this->itensOrcamento[$idx][$campoMes] ?? 0) === $valorNormalizado) {
            return;
        }

        $this->itensOrcamento[$idx][$campoMes] = $valorNormalizado;
        $this->marcarItemEHeaderEmRevisao($idx);
    }

    /**
     * Atualiza campos cadastrais da linha (descricao, natureza_codigo, cenario) isolados por $tempId
     */
    public function atualizarCampoItem(string $tempId, string $campo, $valor): void
    {
        if (!in_array($campo, ['descricao', 'natureza_codigo', 'cenario'], true)) {
            return;
        }

        $idx = $this->encontrarIndicePorTempId($tempId);
        if ($idx === null || !$this->itemPodeSerEditado($this->itensOrcamento[$idx])) {
            return;
        }

        $valorLimpo = trim((string) $valor);
        $this->itensOrcamento[$idx][$campo] = $valorLimpo;

        if ($campo === 'natureza_codigo') {
            $nat = Natureza::where('codigo', $valorLimpo)->first();
            $this->itensOrcamento[$idx]['natureza_nome'] = $nat ? $nat->descricao : '';
            $this->itensOrcamento[$idx]['categoria'] = $nat ? ($nat->categoria ?? '') : '';
            if (empty($this->itensOrcamento[$idx]['descricao']) && $nat) {
                $this->itensOrcamento[$idx]['descricao'] = $nat->descricao;
            }
        }

        $this->marcarItemEHeaderEmRevisao($idx);
    }

    private function marcarItemEHeaderEmRevisao(int $idx): void
    {
        $this->itensOrcamento[$idx]['status'] = 'Em revisão/atualização';

        if (in_array($this->orcamento->status, ['Importado', 'Criado'], true)) {
            $this->orcamento->update(['status' => 'Em revisão/atualização']);
            $this->orcamento->status = 'Em revisão/atualização';
        }
    }

    /**
     * Botão individual na linha: "Liberar para Aprovação"
     */
    public function liberarItemParaAprovacao(int $index): void
    {
        if ($this->isLockedGlobal || !isset($this->itensOrcamento[$index])) {
            return;
        }

        $item = $this->itensOrcamento[$index];
        $tempId = (string) $item['temp_id'];

        if (!in_array($tempId, $this->itensClicados, true)) {
            $this->dispatch('erro', msg: 'Clique primeiro sobre o item para revisar os meses antes de liberá-lo para aprovação.');
            return;
        }

        if (empty(trim($item['natureza_codigo'] ?? ''))) {
            $this->dispatch('erro', msg: 'Selecione a Natureza Financeira desta despesa antes de liberar para aprovação.');
            return;
        }

        if (empty(trim($item['descricao'] ?? ''))) {
            $this->dispatch('erro', msg: 'Preencha a Descrição da Despesa antes de liberar para aprovação.');
            return;
        }

        $this->itensOrcamento[$index]['status'] = 'Aprovação Pendente';

        $itemSalvo = $this->persistirItemIndividual($index);
        $this->itensOrcamento[$index]['id'] = $itemSalvo->id;
        $this->itensOrcamento[$index]['temp_id'] = 'db_' . $itemSalvo->id;
        $this->itensClicados[] = 'db_' . $itemSalvo->id;

        if (in_array($this->orcamento->status, ['Importado', 'Criado'], true)) {
            $this->orcamento->update(['status' => 'Em revisão/atualização']);
            $this->orcamento->status = 'Em revisão/atualização';
        }

        if ($this->todosItensLiberados()) {
            $this->modalConfirmarEnvioAberto = true;
        } else {
            $this->dispatch('sucesso', msg: "Despesa #{$item['id_despesa']} liberada para aprovação!");
        }
    }

    /**
     * Ação em lote (Slide 46): Libera um grupo de despesas selecionadas para "Aprovação Pendente"
     */
    public function liberarSelecionadosParaAprovacao(): void
    {
        if ($this->isLockedGlobal || empty($this->itensSelecionados)) {
            $this->dispatch('erro', msg: 'Selecione pelo menos uma despesa para liberar em lote.');
            return;
        }

        $liberados = 0;
        foreach ($this->itensOrcamento as $idx => $item) {
            $tempId = (string) $item['temp_id'];
            if (!in_array($tempId, $this->itensSelecionados, true)) {
                continue;
            }

            if (in_array($item['status'], ['Aprovada Parcialmente', 'Aprovada', 'Aprovado'], true)) {
                continue;
            }

            if ($item['status'] === 'Aguardando Revisão pelo Responsável') {
                $this->dispatch('erro', msg: "A despesa #{$item['id_despesa']} está marcada como 'Aguardando Revisão pelo Responsável'. Revise seus valores antes de liberá-la.");
                return;
            }

            if (empty(trim($item['natureza_codigo'] ?? ''))) {
                $this->dispatch('erro', msg: "A despesa #{$item['id_despesa']} não possui Natureza Financeira informada.");
                return;
            }

            $this->itensOrcamento[$idx]['status'] = 'Aprovação Pendente';
            $salvo = $this->persistirItemIndividual($idx);
            $this->itensOrcamento[$idx]['id'] = $salvo->id;
            $this->itensOrcamento[$idx]['temp_id'] = 'db_' . $salvo->id;
            $liberados++;
        }

        $this->itensSelecionados = [];
        $this->selecionarTudo = false;

        if ($this->todosItensLiberados()) {
            $this->modalConfirmarEnvioAberto = true;
        } else {
            $this->dispatch('sucesso', msg: "{$liberados} despesa(s) liberada(s) para aprovação!");
        }
    }

    public function updatedSelecionarTudo($value): void
    {
        if ($value) {
            $this->itensSelecionados = collect($this->itensOrcamento)
                ->filter(fn($i) => $this->itemPodeSerEditado($i))
                ->pluck('temp_id')
                ->map(fn($id) => (string) $id)
                ->values()
                ->toArray();
        } else {
            $this->itensSelecionados = [];
        }
    }

    public function reabrirItemParaEdicao(int $index): void
    {
        if ($this->isLockedGlobal || !isset($this->itensOrcamento[$index])) {
            return;
        }

        if (in_array($this->itensOrcamento[$index]['status'] ?? '', ['Aprovada', 'Aprovado', 'Aprovada Parcialmente'], true)) {
            return;
        }

        $this->itensOrcamento[$index]['status'] = 'Em revisão/atualização';
        $this->persistirItemIndividual($index);
        $this->selecionarItem($index);
    }

    public function todosItensLiberados(): bool
    {
        if (empty($this->itensOrcamento)) {
            return false;
        }

        foreach ($this->itensOrcamento as $item) {
            $st = $item['status'] ?? 'Importado';
            if (!in_array($st, ['Aprovação Pendente', 'Aprovada', 'Aprovado', 'Aprovada Parcialmente'], true)) {
                return false;
            }
        }

        return true;
    }

    private function gerarProximoIdDespesa(): string
    {
        $maior = 0;
        foreach ($this->itensOrcamento as $item) {
            $num = (int) preg_replace('/\D/', '', (string) ($item['id_despesa'] ?? '0'));
            if ($num > $maior) {
                $maior = $num;
            }
        }
        return str_pad((string) ($maior + 1), 3, '0', STR_PAD_LEFT);
    }

    private function persistirItemIndividual(int $index): OrcamentoItem
    {
        $dataItem = $this->itensOrcamento[$index];
        $observacao = trim($dataItem['descricao'] ?? '');
        $naturezaNome = $dataItem['natureza_nome'] ?? '';
        $descricaoParaSalvar = $observacao !== '' ? $observacao : ($naturezaNome ?: 'Despesa Orçamentária');
        $idDespesa = !empty($dataItem['id_despesa']) ? $dataItem['id_despesa'] : str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT);

        $idReal = (!empty($dataItem['id']) && is_numeric($dataItem['id'])) ? (int) $dataItem['id'] : null;

        return OrcamentoItem::updateOrCreate(
            ['id' => $idReal, 'orcamento_id' => $this->orcamento->id],
            [
                'id_despesa'      => $idDespesa,
                'natureza_codigo' => trim($dataItem['natureza_codigo']),
                'cenario'         => (string) ($dataItem['cenario'] ?? '1'),
                'descricao'       => $descricaoParaSalvar, 
                'status'          => $dataItem['status'] ?? 'Em revisão/atualização',
                'previsto_jan'    => (float) ($dataItem['previsto_jan'] ?? 0), 'valor_jan' => (float) ($dataItem['valor_jan'] ?? 0),
                'previsto_fev'    => (float) ($dataItem['previsto_fev'] ?? 0), 'valor_fev' => (float) ($dataItem['valor_fev'] ?? 0),
                'previsto_mar'    => (float) ($dataItem['previsto_mar'] ?? 0), 'valor_mar' => (float) ($dataItem['valor_mar'] ?? 0),
                'previsto_abr'    => (float) ($dataItem['previsto_abr'] ?? 0), 'valor_abr' => (float) ($dataItem['valor_abr'] ?? 0),
                'previsto_mai'    => (float) ($dataItem['previsto_mai'] ?? 0), 'valor_mai' => (float) ($dataItem['valor_mai'] ?? 0),
                'previsto_jun'    => (float) ($dataItem['previsto_jun'] ?? 0), 'valor_jun' => (float) ($dataItem['valor_jun'] ?? 0),
                'previsto_jul'    => (float) ($dataItem['previsto_jul'] ?? 0), 'valor_jul' => (float) ($dataItem['valor_jul'] ?? 0),
                'previsto_ago'    => (float) ($dataItem['previsto_ago'] ?? 0), 'valor_ago' => (float) ($dataItem['valor_ago'] ?? 0),
                'previsto_set'    => (float) ($dataItem['previsto_set'] ?? 0), 'valor_set' => (float) ($dataItem['valor_set'] ?? 0),
                'previsto_out'    => (float) ($dataItem['previsto_out'] ?? 0), 'valor_out' => (float) ($dataItem['valor_out'] ?? 0),
                'previsto_nov'    => (float) ($dataItem['previsto_nov'] ?? 0), 'valor_nov' => (float) ($dataItem['valor_nov'] ?? 0),
                'previsto_dez'    => (float) ($dataItem['previsto_dez'] ?? 0), 'valor_dez' => (float) ($dataItem['valor_dez'] ?? 0),
            ]
        );
    }

    public function adicionarItem(): void
    {
        if ($this->isLockedGlobal) return;

        $tempId = uniqid('novo_');
        $proximoIdDespesa = $this->gerarProximoIdDespesa();

        $this->itensOrcamento[] = [
            'temp_id'         => $tempId,
            'id'              => null,
            'id_despesa'      => $proximoIdDespesa,
            'natureza_codigo' => '',
            'natureza_nome'   => '',
            'categoria'       => '',
            'cenario'         => '1',
            'descricao'       => '',
            'status'          => 'Em revisão/atualização',
            'avaliacoes'      => [],
            'previsto_jan' => 0, 'valor_jan' => 0, 'previsto_fev' => 0, 'valor_fev' => 0,
            'previsto_mar' => 0, 'valor_mar' => 0, 'previsto_abr' => 0, 'valor_abr' => 0,
            'previsto_mai' => 0, 'valor_mai' => 0, 'previsto_jun' => 0, 'valor_jun' => 0,
            'previsto_jul' => 0, 'valor_jul' => 0, 'previsto_ago' => 0, 'valor_ago' => 0,
            'previsto_set' => 0, 'valor_set' => 0, 'previsto_out' => 0, 'valor_out' => 0,
            'previsto_nov' => 0, 'valor_nov' => 0, 'previsto_dez' => 0, 'valor_dez' => 0,
        ];

        if (in_array($this->orcamento->status, ['Importado', 'Criado'], true)) {
            $this->orcamento->update(['status' => 'Em revisão/atualização']);
            $this->orcamento->status = 'Em revisão/atualização';
        }

        $novoIdx = count($this->itensOrcamento) - 1;
        $this->selecionarItem($novoIdx);
        $this->dispatch('item-adicionado');
    }

    public function removerItem(int $index): void
    {
        if ($this->isLockedGlobal || !isset($this->itensOrcamento[$index])) return;

        if (in_array($this->itensOrcamento[$index]['status'] ?? '', ['Aprovada', 'Aprovado', 'Aprovada Parcialmente'], true)) {
            $this->dispatch('erro', msg: 'Não é permitido remover um item já aprovado.');
            return;
        }

        if (!empty($this->itensOrcamento[$index]['id'])) {
            $this->itensRemovidos[] = $this->itensOrcamento[$index]['id'];
        }

        unset($this->itensOrcamento[$index]);
        $this->itensOrcamento = array_values($this->itensOrcamento);

        if (empty($this->itensOrcamento)) {
            $this->adicionarItem();
        } else {
            $this->selecionarItem(max(0, min($index, count($this->itensOrcamento) - 1)));
        }

        $this->dispatch('sucesso', msg: 'Item removido da lista. Clique em Salvar para confirmar a exclusão.');
    }

    public function removerItensSelecionados(): void
    {
        if ($this->isLockedGlobal || empty($this->itensSelecionados)) {
            return;
        }

        $restantes = [];
        foreach ($this->itensOrcamento as $item) {
            if (in_array((string) $item['temp_id'], $this->itensSelecionados, true) && $this->itemPodeSerEditado($item)) {
                if (!empty($item['id'])) {
                    $this->itensRemovidos[] = $item['id'];
                }
            } else {
                $restantes[] = $item;
            }
        }

        $this->itensOrcamento = array_values($restantes);
        $this->itensSelecionados = [];
        $this->selecionarTudo = false;

        if (empty($this->itensOrcamento)) {
            $this->adicionarItem();
        } else {
            $this->selecionarItem(0);
        }

        $this->dispatch('sucesso', msg: 'Despesas selecionadas removidas. Clique em Salvar para confirmar.');
    }

    public function salvarOrcamento(): void
    {
        if ($this->isLockedGlobal) {
            $this->dispatch('erro', msg: 'O orçamento está bloqueado para edições no momento.');
            return;
        }

        foreach ($this->itensOrcamento as $item) {
            if (empty(trim($item['natureza_codigo'] ?? ''))) {
                $this->dispatch('erro', msg: "A despesa #{$item['id_despesa']} precisa ter uma Natureza Financeira selecionada antes de salvar.");
                return;
            }
        }

        if (!empty($this->itensRemovidos)) {
            OrcamentoItem::whereIn('id', $this->itensRemovidos)->delete();
            $this->itensRemovidos = [];
        }

        foreach ($this->itensOrcamento as $idx => $item) {
            $this->persistirItemIndividual($idx);
        }

        $novoStatusOrcamento = $this->orcamento->status;
        if (in_array($novoStatusOrcamento, ['Importado', 'Criado'], true)) {
            $novoStatusOrcamento = 'Em revisão/atualização';
        }

        $this->orcamento->update([
            'descricao_despesa' => $this->justificativaGeral,
            'status'            => $novoStatusOrcamento,
        ]);

        $this->orcamento->refresh();
        $this->carregarItens();
        $this->dispatch('sucesso', msg: 'Alterações salvas com sucesso!');
    }

    /**
     * Submete o orçamento para aprovação respeitando as validações do Slide 46 e a liberação dos itens
     */
    public function finalizarOrcamento()
    {
        if ($this->isLockedGlobal) {
            $this->dispatch('erro', msg: 'Este orçamento já foi enviado para aprovação.');
            return;
        }

        // Slide 46: Não permite submeter caso exista alguma despesa com status "Aguardando Revisão pelo Responsável"
        foreach ($this->itensOrcamento as $item) {
            if (($item['status'] ?? '') === 'Aguardando Revisão pelo Responsável') {
                $this->dispatch('erro', msg: "Não é possível submeter: a despesa #{$item['id_despesa']} ainda está com status 'Aguardando Revisão pelo Responsável'.");
                return;
            }
            if (empty(trim($item['natureza_codigo'] ?? ''))) {
                $this->dispatch('erro', msg: "Informe a Natureza Financeira na despesa #{$item['id_despesa']} antes de submeter.");
                return;
            }
        }

        // Valida se todos os itens editáveis foram liberados para aprovação
        if (!$this->todosItensLiberados()) {
            $this->dispatch('erro', msg: 'É necessário clicar em "Liberar para Aprovação" em todos os itens antes de enviar o orçamento.');
            return;
        }

        if (!empty($this->itensRemovidos)) {
            OrcamentoItem::whereIn('id', $this->itensRemovidos)->delete();
            $this->itensRemovidos = [];
        }

        foreach ($this->itensOrcamento as $idx => $item) {
            // Slide 46: As despesas com status "Aprovada Parcialmente", "Aprovada" e "Não Aprovada" não sofrem alteração indevida
            if (!in_array($item['status'], ['Aprovada Parcialmente', 'Aprovada', 'Aprovado', 'Não Aprovada'], true)) {
                $this->itensOrcamento[$idx]['status'] = 'Aprovação Pendente';
            }

            $salvo = $this->persistirItemIndividual($idx);
            if ($salvo->status === 'Aprovação Pendente') {
                OrcamentoAvaliacao::create([
                    'orcamento_id'      => $this->orcamento->id,
                    'orcamento_item_id' => $salvo->id,
                    'user_id'           => auth()->id(),
                    'user_nome'         => auth()->user()->name ?? 'Responsável',
                    'status_aplicado'   => 'Aprovação Pendente',
                    'comentario'        => 'Despesa submetida para aprovação.',
                    'tipo_evento'       => 'liberacao_item',
                ]);
            }
        }

        $this->orcamento->update([
            'descricao_despesa'     => $this->justificativaGeral,
            'status'                => 'Finalizado – Aguardando Aprovação',
            'prazo_edicao'          => null,
            'reabertura_solicitada' => false,
        ]);

        $this->modalConfirmarEnvioAberto = false;
        \App\Modules\Financeiro\Services\OrcamentoNotificacaoService::notificarSubmissaoParaAprovacao($this->orcamento, auth()->user());
        $this->dispatch('sucesso', msg: 'Orçamento submetido para aprovação com sucesso!');

        return redirect()->route('financeiro.orcamentos');
    }

    /**
     * Slide 49: Botão para o responsável finalizar definitivamente o orçamento aprovado e preparar o JSON do Protheus
     */
    public function integrarComProtheus(): void
    {
        if (!in_array($this->orcamento->status, ['Finalizado – Aprovado', 'Aprovado'], true)) {
            $this->dispatch('erro', msg: 'A integração com o Protheus só é permitida quando o status do orçamento for "Finalizado – Aprovado".');
            return;
        }

        app(ProtheusOrcamentoService::class)->enviarParaProtheusSimulado($this->orcamento);

        $this->orcamento->update([
            'status' => 'Finalizado – Integrado ao Protheus',
        ]);

        $this->orcamento->refresh();
        $this->atualizarBloqueioGlobal();

        \App\Modules\Financeiro\Services\OrcamentoNotificacaoService::notificarIntegracaoProtheus($this->orcamento, auth()->user());

        $this->dispatch('sucesso', msg: 'Orçamento finalizado e dados consolidados para a Tabela Intermediária do Protheus!');
    }

    private function notificarAvaliadoresPorEmail(): void
    {
        try {
            $emails = User::permission('financeiro.orcamentos.avaliacao')
                ->pluck('email')
                ->filter()
                ->unique()
                ->values()
                ->toArray();

            if (!empty($emails)) {
                Mail::to($emails)->send(
                    new OrcamentoFinalizadoMail($this->orcamento, auth()->user()->name ?? 'Responsável')
                );
            }
        } catch (\Throwable $e) {
            Log::error("Erro ao enviar e-mail de finalização do orçamento #{$this->orcamento->id}: " . $e->getMessage());
        }
    }

    public function abrirComentariosGerais(): void
    {
        $this->abaComentario = 'geral';
        $this->drawerComentariosAberto = true;
    }

    public function abrirComentariosDoItem(int $index): void
    {
        $this->selecionarItem($index);
        $itemId = $this->itensOrcamento[$index]['id'] ?? null;

        if (!$itemId) {
            $salvo = $this->persistirItemIndividual($index);
            $this->itensOrcamento[$index]['id'] = $salvo->id;
            $itemId = $salvo->id;
        }

        $this->itemComentarioId = (int) $itemId;
        $this->abaComentario = 'item';
        $this->drawerComentariosAberto = true;
    }

    /**
     * A partir do momento em que o orçamento inicia o processo de avaliação ou está em qualquer status de finalização,
     * o editor não pode inserir comentários gerais nem nos itens (apenas leitura).
     */
    public function podeInserirComentarios(): bool
    {
        if ($this->isLockedGlobal) {
            return false;
        }

        $statusSomenteLeitura = [
            'Finalizado – Aguardando Aprovação',
            'Finalizado - Aguardando Aprovação',
            'Finalizado – Aprovado',
            'Finalizado - Aprovado',
            'Finalizado – Integrado ao Protheus',
            'Finalizado - Integrado ao Protheus',
            'Finalizado',
            'Aprovado',
            'Aprovado com ressalvas',
        ];

        return !in_array($this->orcamento->status, $statusSomenteLeitura, true);
    }

    public function adicionarComentarioGlobal(): void
    {
        if (!$this->podeInserirComentarios()) {
            $this->dispatch('erro', msg: 'Não é permitido inserir comentários enquanto o orçamento está em avaliação ou finalizado.');
            return;
        }

        $this->validate(
            ['novoComentarioGeral' => 'required|string|min:2'],
            ['novoComentarioGeral.required' => 'Digite uma mensagem geral antes de enviar.']
        );

        OrcamentoAvaliacao::create([
            'orcamento_id'      => $this->orcamento->id,
            'orcamento_item_id' => null,
            'user_id'           => auth()->id(),
            'user_nome'         => auth()->user()->name,
            'status_aplicado'   => 'Comentário Geral',
            'comentario'        => trim($this->novoComentarioGeral),
            'tipo_evento'       => 'comentario_chat'
        ]);

        $this->novoComentarioGeral = '';
        $this->orcamento->refresh();
        $this->dispatch('comentario-adicionado');
    }

    public function adicionarComentarioPorItem(): void
    {
        if (!$this->podeInserirComentarios()) {
            $this->dispatch('erro', msg: 'Não é permitido inserir comentários nos itens enquanto o orçamento está em avaliação ou finalizado.');
            return;
        }

        $this->validate([
            'itemComentarioId'   => 'required|integer',
            'novoComentarioItem' => 'required|string|min:2',
        ]);

        OrcamentoAvaliacao::create([
            'orcamento_id'      => $this->orcamento->id,
            'orcamento_item_id' => $this->itemComentarioId,
            'user_id'           => auth()->id(),
            'user_nome'         => auth()->user()->name,
            'status_aplicado'   => 'Comentário do Responsável',
            'comentario'        => trim($this->novoComentarioItem),
            'tipo_evento'       => 'comentario_item'
        ]);

        $this->novoComentarioItem = '';
        $this->orcamento->refresh();
        $this->carregarItens();
        $this->dispatch('comentario-adicionado');
    }

    public function solicitarReabertura(): void
    {
        $this->validate(['motivoReabertura' => 'required|string|min:5']);

        $this->orcamento->update([
            'reabertura_solicitada' => true,
            'reabertura_motivo'     => $this->motivoReabertura
        ]);

        OrcamentoAvaliacao::create([
            'orcamento_id'    => $this->orcamento->id,
            'user_id'         => auth()->id(),
            'user_nome'       => auth()->user()->name,
            'status_aplicado' => 'Pedido de Reabertura',
            'comentario'      => $this->motivoReabertura,
            'tipo_evento'     => 'solicitacao_reabertura'
        ]);

        $this->modalReaberturaAberto = false;
        $this->dispatch('sucesso', msg: 'Pedido enviado aos Aprovadores.');
        $this->orcamento->refresh();
    }

    public function voltar()
    {
        return redirect()->route('financeiro.orcamentos');
    }

    public function render()
    {
        $todasNaturezas = Natureza::where('disponivel_orcamento', true)->orderBy('codigo')->get();

        $comentariosGerais = OrcamentoAvaliacao::where('orcamento_id', $this->orcamento->id)
            ->whereNull('orcamento_item_id')
            ->orderBy('created_at', 'asc')
            ->get();

        $comentariosDoItemSelecionado = collect();
        if ($this->itemComentarioId) {
            $comentariosDoItemSelecionado = OrcamentoAvaliacao::where('orcamento_id', $this->orcamento->id)
                ->where('orcamento_item_id', $this->itemComentarioId)
                ->orderBy('created_at', 'asc')
                ->get();
        }

        return view('livewire.financeiro.orcamento-editor', [
            'todasNaturezas'               => $todasNaturezas,
            'comentariosGerais'            => $comentariosGerais,
            'comentariosDoItemSelecionado' => $comentariosDoItemSelecionado,
        ]);
    }
}