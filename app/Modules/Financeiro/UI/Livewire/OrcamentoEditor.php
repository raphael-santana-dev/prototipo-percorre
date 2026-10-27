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

#[Layout('components.layouts.app')]
#[Title('Edição de Orçamento')]
class OrcamentoEditor extends Component
{
    public Orcamento $orcamento;
    public string $justificativaGeral = '';
    public array $itensOrcamento = [];
    public array $itensRemovidos = [];

    // Índice do item atualmente selecionado na tabela "Despesas" (Mestre-Detalhe)
    public int $itemSelecionadoIndex = 0;

    // Lista de temp_ids nos quais o usuário já clicou nesta sessão (habilita o botão "Liberar para Aprovação")
    public array $itensClicados = [];

    // Modal disparado automaticamente ao liberar o último item
    public bool $modalConfirmarEnvioAberto = false;

    // Comentários divididos em 'geral' e 'item'
    public bool $drawerComentariosAberto = false;
    public string $abaComentario = 'geral'; // 'geral' ou 'item'
    public ?int $itemComentarioId = null;
    public string $novoComentarioGeral = '';
    public string $novoComentarioItem = '';

    public bool $modalNovaNaturezaAberto = false;
    public string $novaNaturezaDescricao = '';

    public bool $modalReaberturaAberto = false;
    public string $motivoReabertura = '';

    public int $anoSimulacao = 2026;
    public bool $isLockedGlobal = false;

    public function mount($id)
    {
        $this->orcamento = Orcamento::with([
            'itens' => fn($q) => $q->orderBy('id', 'asc'),
            'itens.natureza',
            'itens.avaliacoes',
            'centroCusto',
            'logsGerais'
        ])->findOrFail($id);
        
        if (!auth()->user()->hasRole('dev|admin') && !auth()->user()->can('financeiro.orcamentos.global')) {
            if (method_exists(auth()->user(), 'centros_de_custo')) {
                $ccPermitidos = auth()->user()->centros_de_custo->pluck('codigo')->toArray();
                abort_unless(in_array($this->orcamento->ccusto, $ccPermitidos), 403, 'Sem permissão para este Centro de Custo.');
            } else {
                abort(403);
            }
        }

        $this->anoSimulacao = session('ano_simulacao_orcamento', date('Y'));
        $this->atualizarBloqueioGlobal();

        $this->justificativaGeral = $this->orcamento->descricao_despesa ?? '';
        $this->carregarItens();

        // Seleciona e marca como clicado o primeiro item por padrão
        if (!empty($this->itensOrcamento)) {
            $this->selecionarItem(0);
        }
    }

    private function atualizarBloqueioGlobal(): void
    {
        // O orçamento fica bloqueado globalmente quando está aguardando aprovação ou já aprovado
        $statusBloqueados = [
            'Finalizado – Aguardando Aprovação',
            'Finalizado – Aprovado',
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

        foreach ($itensOrdenados as $item) {
            $natObj = $item->natureza;
            $natNome = $natObj ? $natObj->descricao : 'Natureza Importada';
            $statusNormalizado = $item->status ?: 'Importado';
            if ($statusNormalizado === 'Criado') {
                $statusNormalizado = 'Importado';
            }

            $this->itensOrcamento[] = [
                'temp_id'         => 'db_' . $item->id,
                'id'              => $item->id, 
                'natureza_codigo' => $item->natureza_codigo, 
                'natureza_nome'   => $natNome,
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
        }

        if (empty($this->itensOrcamento) && !$this->isLockedGlobal) {
            $this->adicionarItem();
        }

        if ($this->itemSelecionadoIndex >= count($this->itensOrcamento)) {
            $this->itemSelecionadoIndex = max(0, count($this->itensOrcamento) - 1);
        }
    }

    /**
     * Verifica se uma linha específica pode ser editada pelo usuário no momento
     */
    public function itemPodeSerEditado(array $item): bool
    {
        if ($this->isLockedGlobal) {
            return false;
        }

        $statusItem = $item['status'] ?? 'Importado';

        // Se o orçamento estiver em ciclo de revisão após avaliação, apenas os itens reprovados/em revisão podem ser editados
        if ($this->orcamento->status === 'Aprovação – Aguardando Revisão pelo Responsável') {
            return in_array($statusItem, ['Não Aprovado', 'Reprovado', 'Reprovado parcialmente', 'Em revisão/atualização'], true);
        }

        // Itens já aprovados definitivamente não podem ser alterados
        if ($statusItem === 'Aprovado') {
            return false;
        }

        return true;
    }

    /**
     * Seleciona um item na tabela da esquerda para exibir seus 12 meses na direita
     * e registra que o usuário clicou no item (habilitando "Liberar para Aprovação")
     */
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
     * Quando o usuário altera qualquer valor mensal, descrição ou natureza de um item,
     * o status do item e do orçamento passam imediatamente para "Em revisão/atualização"
     */
    public function updatedItensOrcamento($value, $name): void
    {
        $parts = explode('.', (string) $name);
        $index = (int) ($parts[0] ?? 0);
        $campo = $parts[1] ?? '';

        if (!isset($this->itensOrcamento[$index])) {
            return;
        }

        if ($campo === 'natureza_codigo') {
            $nat = Natureza::where('codigo', $value)->first();
            $this->itensOrcamento[$index]['natureza_nome'] = $nat ? $nat->descricao : 'Natureza não encontrada';
            if (empty(trim($this->itensOrcamento[$index]['descricao'] ?? '')) && $nat) {
                $this->itensOrcamento[$index]['descricao'] = $nat->descricao;
            }
        }

        if (!$this->itemPodeSerEditado($this->itensOrcamento[$index])) {
            return;
        }

        // Altera o status do item alterado para "Em revisão/atualização"
        $this->itensOrcamento[$index]['status'] = 'Em revisão/atualização';

        // Altera o status do orçamento para "Em revisão/atualização" se estava Importado/Criado
        if (in_array($this->orcamento->status, ['Importado', 'Criado'], true)) {
            $this->orcamento->update(['status' => 'Em revisão/atualização']);
            $this->orcamento->status = 'Em revisão/atualização';
        }
    }

    /**
     * Botão "Liberar para Aprovação" em cada linha de item
     */
    public function liberarItemParaAprovacao(int $index): void
    {
        if ($this->isLockedGlobal || !isset($this->itensOrcamento[$index])) {
            return;
        }

        $item = $this->itensOrcamento[$index];
        $tempId = (string) $item['temp_id'];

        // Só permite se o usuário tiver clicado no item
        if (!in_array($tempId, $this->itensClicados, true)) {
            $this->dispatch('erro', msg: 'Clique sobre o item para conferir seus valores mensais antes de liberá-lo para aprovação.');
            return;
        }

        if (empty(trim($item['natureza_codigo'] ?? ''))) {
            $this->dispatch('erro', msg: 'Selecione uma Natureza Financeira válida para este item antes de liberar.');
            return;
        }

        $this->itensOrcamento[$index]['status'] = 'Aprovação Pendente';

        // Persiste o item imediatamente mantendo a ordem por ID
        $itemSalvo = $this->persistirItemIndividual($index);
        $this->itensOrcamento[$index]['id'] = $itemSalvo->id;
        $this->itensOrcamento[$index]['temp_id'] = 'db_' . $itemSalvo->id;
        $this->itensClicados[] = 'db_' . $itemSalvo->id;

        if (in_array($this->orcamento->status, ['Importado', 'Criado'], true)) {
            $this->orcamento->update(['status' => 'Em revisão/atualização']);
            $this->orcamento->status = 'Em revisão/atualização';
        }

        // Verifica se TODOS os itens já estão prontos ("Aprovação Pendente" ou já "Aprovado")
        if ($this->todosItensLiberados()) {
            $this->modalConfirmarEnvioAberto = true;
        } else {
            $this->dispatch('sucesso', msg: 'Item liberado para aprovação!');
        }
    }

    /**
     * Permite voltar um item de "Aprovação Pendente" para "Em revisão/atualização" antes do envio global
     */
    public function reabrirItemParaEdicao(int $index): void
    {
        if ($this->isLockedGlobal || !isset($this->itensOrcamento[$index])) {
            return;
        }

        if (($this->itensOrcamento[$index]['status'] ?? '') === 'Aprovado') {
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
            if (!in_array($st, ['Aprovação Pendente', 'Aprovado'], true)) {
                return false;
            }
        }

        return true;
    }

    private function persistirItemIndividual(int $index): OrcamentoItem
    {
        $dataItem = $this->itensOrcamento[$index];
        $observacao = trim($dataItem['descricao'] ?? '');
        $naturezaNome = $dataItem['natureza_nome'] ?? '';
        $descricaoParaSalvar = $observacao !== '' ? $observacao : ($naturezaNome ?: 'Despesa Orçamentária');

        $idReal = (!empty($dataItem['id']) && is_numeric($dataItem['id'])) ? (int) $dataItem['id'] : null;

        return OrcamentoItem::updateOrCreate(
            ['id' => $idReal, 'orcamento_id' => $this->orcamento->id],
            [
                'natureza_codigo' => trim($dataItem['natureza_codigo']), 
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
        $this->itensOrcamento[] = [
            'temp_id'         => $tempId,
            'id'              => null,
            'natureza_codigo' => '',
            'natureza_nome'   => '',
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

        $novoIdx = count($this->itensOrcamento) - 1;
        $this->selecionarItem($novoIdx);
        $this->dispatch('item-adicionado');
    }

    public function removerItem(int $index): void
    {
        if ($this->isLockedGlobal || !isset($this->itensOrcamento[$index])) return;

        if (($this->itensOrcamento[$index]['status'] ?? '') === 'Aprovado') {
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
    }

    public function salvarOrcamento(): void
    {
        if ($this->isLockedGlobal) {
            $this->dispatch('erro', msg: 'O orçamento está bloqueado para edições no momento.');
            return;
        }

        foreach ($this->itensOrcamento as $idx => $item) {
            if (empty(trim($item['natureza_codigo'] ?? ''))) {
                $this->dispatch('erro', msg: 'Todas as despesas precisam ter uma Natureza selecionada antes de salvar.');
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
        $this->dispatch('sucesso', msg: 'Alterações salvas com sucesso (mantendo a ordenação por ID)!');
    }

    public function finalizarOrcamento()
    {
        if ($this->isLockedGlobal) {
            $this->dispatch('erro', msg: 'Este orçamento já foi enviado para aprovação.');
            return;
        }

        // Regra obrigatória: só pode enviar se tiver clicado em "Liberar para Aprovação" em todos os itens
        if (!$this->todosItensLiberados()) {
            $this->dispatch('erro', msg: 'É necessário clicar em "Liberar para Aprovação" em todos os itens antes de enviar o orçamento.');
            return;
        }

        foreach ($this->itensOrcamento as $idx => $item) {
            if (empty(trim($item['natureza_codigo'] ?? ''))) {
                $this->dispatch('erro', msg: 'Todas as despesas devem ter uma Natureza válida selecionada.');
                return;
            }
        }

        if (!empty($this->itensRemovidos)) {
            OrcamentoItem::whereIn('id', $this->itensRemovidos)->delete();
            $this->itensRemovidos = [];
        }

        foreach ($this->itensOrcamento as $idx => $item) {
            $salvo = $this->persistirItemIndividual($idx);
            if ($salvo->status === 'Aprovação Pendente') {
                OrcamentoAvaliacao::create([
                    'orcamento_id'      => $this->orcamento->id,
                    'orcamento_item_id' => $salvo->id,
                    'user_id'           => auth()->id(),
                    'user_nome'         => auth()->user()->name ?? 'Responsável',
                    'status_aplicado'   => 'Aprovação Pendente',
                    'comentario'        => 'Item liberado e enviado para aprovação.',
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
        $this->notificarAvaliadoresPorEmail();
        $this->dispatch('sucesso', msg: 'Orçamento enviado para aprovação com sucesso!');

        return redirect()->route('financeiro.orcamentos');
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

    // =========================================================================
    // HISTÓRICO E COMENTÁRIOS (GERAIS vs POR ITEM)
    // =========================================================================

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

    public function adicionarComentarioGlobal(): void
    {
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
        $this->validate([
            'itemComentarioId'   => 'required|integer',
            'novoComentarioItem' => 'required|string|min:2',
        ], [
            'itemComentarioId.required'   => 'Selecione uma despesa.',
            'novoComentarioItem.required' => 'Digite um comentário para o item.',
        ]);

        OrcamentoAvaliacao::create([
            'orcamento_id'      => $this->orcamento->id,
            'orcamento_item_id' => $this->itemComentarioId,
            'user_id'           => auth()->id(),
            'user_nome'         => auth()->user()->name,
            'status_aplicado'   => 'Comentário no Item',
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
        $this->validate(['motivoReabertura' => 'required|string|min:5'], ['motivoReabertura.required' => 'O motivo é obrigatório.']);

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
        $this->dispatch('sucesso', msg: 'Pedido enviado aos Aprovadores. Aguarde a análise.');
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