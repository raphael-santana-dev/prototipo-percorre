<?php

namespace App\Modules\Matricula\UI\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Inscricao;
use App\Modules\Matricula\Domain\Models\DocumentoExigido;
use App\Modules\Matricula\Domain\Models\DocumentoMatricula;
use Illuminate\Support\Facades\Storage;
use App\Traits\ComPadraoListagem;

#[Layout('components.layouts.app')]
#[Title('Acompanhamento de Matrículas')]
class ProcessoMatriculaManager extends Component
{
    use WithPagination, ComPadraoListagem;

    public $termoBusca = '';
    
    // Controle do Modal de Dossiê
    public $modalDossieAberto = false;
    public $inscricaoSelecionada = null;
    public $documentosExigidos = [];
    public $documentosEnviados = [];

    public function mount()
    {
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('matricula.listar'), 403, 'Acesso restrito.');
    }

    public function updatingTermoBusca()
    {
        $this->resetPage();
    }

    public function getHeadersProperty()
    {
        return [
            ['key' => 'id', 'label' => '#ID', 'sortable' => true, 'class' => 'w-16'],
            ['key' => 'candidato', 'label' => 'Candidato / Curso', 'sortable' => false],
            ['key' => 'status', 'label' => 'Progresso dos Documentos', 'sortable' => false, 'class' => 'text-center'],
            ['key' => 'etapa', 'label' => 'Etapa Atual', 'sortable' => true, 'class' => 'text-center'],
            ['key' => 'acoes', 'label' => 'Dossiê', 'sortable' => false, 'class' => 'text-right w-24'],
        ];
    }

    public function abrirDossie($inscricaoId)
    {
        $this->inscricaoSelecionada = Inscricao::with(['curso', 'unidade'])->findOrFail($inscricaoId);
        
        // Puxa as regras de documentos para o ciclo desse aluno
        $this->documentosExigidos = DocumentoExigido::where('ciclo_id', $this->inscricaoSelecionada->ciclo_id)->get();
        
        // Puxa o que o aluno enviou
        $this->documentosEnviados = DocumentoMatricula::where('inscricao_id', $this->inscricaoSelecionada->id)
                                                      ->get()
                                                      ->keyBy('documento_exigido_id');

        $this->modalDossieAberto = true;
    }

    public function aprovarDocumento($documentoMatriculaId)
    {
        $doc = DocumentoMatricula::findOrFail($documentoMatriculaId);
        $doc->update(['status_analise' => 'aprovado_manual', 'avaliado_por' => auth()->id()]);
        
        $this->verificarConclusaoMatricula($doc->inscricao_id);
        
        // Recarrega os dados do modal
        $this->abrirDossie($doc->inscricao_id);
        $this->dispatch('sucesso', msg: 'Documento aprovado manualmente com sucesso!');
    }

    public function reprovarDocumento($documentoMatriculaId)
    {
        $doc = DocumentoMatricula::findOrFail($documentoMatriculaId);
        
        if (Storage::disk('local')->exists($doc->arquivo_caminho)) {
            Storage::disk('local')->delete($doc->arquivo_caminho);
        }

        $doc->update([
            'status_analise' => 'pendente',
            'tentativas_ia' => 0,
            'avaliado_por' => auth()->id(),
            'log_ia' => array_merge(is_array($doc->log_ia) ? $doc->log_ia : [], ['motivo_rejeicao_humana' => 'A secretaria rejeitou o documento. Envie uma nova foto.'])
        ]);

        $this->abrirDossie($doc->inscricao_id);
        $this->dispatch('sucesso', msg: 'Documento reprovado. O portal foi reaberto para o candidato.');
    }

    private function verificarConclusaoMatricula($inscricaoId)
    {
        $inscricao = Inscricao::find($inscricaoId);
        $docsObrigatorios = DocumentoExigido::where('ciclo_id', $inscricao->ciclo_id)->where('is_obrigatorio', true)->pluck('id');
        $docsAprovados = DocumentoMatricula::where('inscricao_id', $inscricao->id)
                                           ->whereIn('documento_exigido_id', $docsObrigatorios)
                                           ->whereIn('status_analise', ['valido_ia', 'aprovado_manual'])
                                           ->count();

        if ($docsAprovados >= count($docsObrigatorios)) {
            $inscricao->update(['etapa_atual' => 3]);
        }
    }

    public function render()
    {
        // Filtra apenas quem tem o token (ou seja, foi Aprovado e entrou no fluxo de matrícula)
        $query = Inscricao::with(['curso', 'ciclo'])
            ->whereNotNull('token_matricula')
            ->when($this->termoBusca, function ($q) {
                $q->where('nome', 'ilike', '%' . $this->termoBusca . '%')
                  ->orWhere('cpf', 'ilike', '%' . $this->termoBusca . '%');
            });

        if ($this->ordenacaoCampo) {
            $query->orderBy($this->ordenacaoCampo, $this->ordenacaoDirecao);
        } else {
            $query->orderBy('updated_at', 'desc');
        }

        return view('livewire.matricula.processo-matricula-manager', [
            'registros' => $query->paginate($this->porPagina)
        ]);
    }
}