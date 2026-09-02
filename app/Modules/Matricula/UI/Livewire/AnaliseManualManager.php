<?php

namespace App\Modules\Matricula\UI\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Modules\Matricula\Domain\Models\DocumentoMatricula;
use App\Modules\Matricula\Domain\Models\DocumentoExigido;
use Illuminate\Support\Facades\Storage;
use App\Traits\ComPadraoListagem;

#[Layout('components.layouts.app')]
#[Title('Central de Análise de Documentos')]
class AnaliseManualManager extends Component
{
    use WithPagination, ComPadraoListagem;

    public $modalAnaliseAberto = false;
    public $documentoSelecionado = null;
    
    // Preview Seguro da Imagem
    public $imagemBase64 = null;
    public $imagemMime = null;
    
    // Ação de Reprovação
    public $motivoReprovacao = '';

    public function mount()
    {
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('matricula.analisar'), 403, 'Acesso restrito.');
    }

    public function getHeadersProperty()
    {
        return [
            ['key' => 'id', 'label' => '#', 'sortable' => true, 'class' => 'w-16'],
            ['key' => 'candidato', 'label' => 'Candidato e Inscrição', 'sortable' => false],
            ['key' => 'documento', 'label' => 'Documento Exigido', 'sortable' => false],
            ['key' => 'tentativas', 'label' => 'Ações da IA', 'sortable' => false],
            ['key' => 'acoes', 'label' => 'Análise', 'sortable' => false, 'class' => 'text-right w-32'],
        ];
    }

    public function abrirAnalise($id)
    {
        $this->reset(['motivoReprovacao', 'imagemBase64', 'imagemMime']);
        
        $this->documentoSelecionado = DocumentoMatricula::with(['inscricao.curso', 'documentoExigido'])->findOrFail($id);
        
        // Converte o arquivo protegido para Base64 para exibir com segurança na tela
        if (Storage::disk('local')->exists($this->documentoSelecionado->arquivo_caminho)) {
            $caminhoAbsoluto = Storage::disk('local')->path($this->documentoSelecionado->arquivo_caminho);
            $this->imagemMime = mime_content_type($caminhoAbsoluto);
            $this->imagemBase64 = base64_encode(file_get_contents($caminhoAbsoluto));
        }

        $this->modalAnaliseAberto = true;
    }

    public function aprovar()
    {
        if (!$this->documentoSelecionado) return;

        $this->documentoSelecionado->update([
            'status_analise' => 'aprovado_manual',
            'avaliado_por' => auth()->id()
        ]);

        $this->verificarConclusaoMatricula($this->documentoSelecionado->inscricao);

        $this->modalAnaliseAberto = false;
        $this->dispatch('sucesso', msg: 'Documento aprovado manualmente com sucesso!');
    }

    public function reprovar()
    {
        $this->validate([
            'motivoReprovacao' => 'required|min:5'
        ], [
            'motivoReprovacao.required' => 'Escreva o motivo para o candidato saber o que corrigir.'
        ]);

        // Zera as tentativas e volta para 'pendente' para o candidato tentar de novo
        $this->documentoSelecionado->update([
            'status_analise' => 'pendente',
            'tentativas_ia' => 0,
            'avaliado_por' => auth()->id(),
            'log_ia' => array_merge(
                is_array($this->documentoSelecionado->log_ia) ? $this->documentoSelecionado->log_ia : [], 
                ['motivo_rejeicao_humana' => $this->motivoReprovacao]
            )
        ]);

        // Apaga o arquivo físico errado para liberar espaço
        if (Storage::disk('local')->exists($this->documentoSelecionado->arquivo_caminho)) {
            Storage::disk('local')->delete($this->documentoSelecionado->arquivo_caminho);
        }

        $this->modalAnaliseAberto = false;
        $this->dispatch('sucesso', msg: 'Documento reprovado. O portal do candidato foi reaberto para reenvio.');
    }

    private function verificarConclusaoMatricula($inscricao)
    {
        $docsObrigatorios = DocumentoExigido::where('ciclo_id', $inscricao->ciclo_id)
                                            ->where('is_obrigatorio', true)
                                            ->pluck('id');

        $docsAprovados = DocumentoMatricula::where('inscricao_id', $inscricao->id)
                                           ->whereIn('documento_exigido_id', $docsObrigatorios)
                                           ->whereIn('status_analise', ['valido_ia', 'aprovado_manual'])
                                           ->count();

        // Se a quantidade de arquivos válidos for igual a quantidade exigida, finaliza!
        if ($docsAprovados >= count($docsObrigatorios)) {
            $inscricao->update(['etapa_atual' => 'Matriculado']);
            // Opcional: Aqui você pode disparar um e-mail de "Matrícula Concluída com Sucesso".
        }
    }

    public function render()
    {
        $pendentes = DocumentoMatricula::with(['inscricao', 'documentoExigido'])
            ->where('status_analise', 'analise_manual')
            ->orderBy('updated_at', 'asc')
            ->paginate($this->porPagina);

        return view('livewire.matricula.analise-manual-manager', [
            'registros' => $pendentes
        ]);
    }
}