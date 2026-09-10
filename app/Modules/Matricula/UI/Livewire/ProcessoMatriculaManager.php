<?php

namespace App\Modules\Matricula\UI\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Inscricao;
use App\Models\Curso;
use App\Modules\Unidade\Domain\Models\Unidade;
use App\Modules\Turno\Domain\Models\Turno;
use App\Modules\Matricula\Domain\Models\DocumentoExigido;
use App\Modules\Matricula\Domain\Models\DocumentoMatricula;
use Illuminate\Support\Facades\Storage;
use App\Traits\ComPadraoListagem;

#[Layout('components.layouts.app')]
#[Title('Acompanhamento de Matrículas')]
class ProcessoMatriculaManager extends Component
{
    use WithPagination, ComPadraoListagem;

    // Controle de Abas
    public $abaAtiva = 'dossies';

    // Filtros Globais (Aplicam-se em ambas as abas)
    public $filtroBusca = '';
    public $filtroCurso = '';
    public $filtroUnidade = '';
    public $filtroTurno = '';
    public $filtroEtapa = '';

    // Ordenação Aba 2 (Fila da IA)
    public $ordenacaoCampoRevisao = '';
    public $ordenacaoDirecaoRevisao = 'asc';
    
    // Modal e Dossiê
    public $modalDossieAberto = false;
    public $inscricaoSelecionada = null;
    public $documentosExigidos = [];
    public $documentosEnviados = [];
    public $motivosReprovacao = [];

    public function mount()
    {
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('matricula.listar'), 403, 'Acesso restrito.');
    }

    public function updating($nomePropriedade)
    {
        if (in_array($nomePropriedade, ['filtroBusca', 'filtroCurso', 'filtroUnidade', 'filtroTurno', 'filtroEtapa'])) {
            $this->resetPage();
            $this->resetPage('revisaoPage');
        }
    }

    public function limparFiltros()
    {
        $this->reset(['filtroBusca', 'filtroCurso', 'filtroUnidade', 'filtroTurno', 'filtroEtapa']);
        $this->resetPage();
        $this->resetPage('revisaoPage');
    }

    // ==========================================
    // CONTROLE DE ORDENAÇÃO (X-TABLE) INTELIGENTE
    // ==========================================
    public function sortBy($campo) { $this->aplicarOrdenacao($campo); }
    public function ordenar($campo) { $this->aplicarOrdenacao($campo); }
    public function sort($campo) { $this->aplicarOrdenacao($campo); }

    private function aplicarOrdenacao($campo)
    {
        if ($this->abaAtiva === 'revisao') {
            if ($this->ordenacaoCampoRevisao === $campo) {
                $this->ordenacaoDirecaoRevisao = $this->ordenacaoDirecaoRevisao === 'asc' ? 'desc' : 'asc';
            } else {
                $this->ordenacaoCampoRevisao = $campo;
                $this->ordenacaoDirecaoRevisao = 'asc';
            }
            $this->resetPage('revisaoPage');
        } else {
            if ($this->ordenacaoCampo === $campo) {
                $this->ordenacaoDirecao = $this->ordenacaoDirecao === 'asc' ? 'desc' : 'asc';
            } else {
                $this->ordenacaoCampo = $campo;
                $this->ordenacaoDirecao = 'asc';
            }
            $this->resetPage();
        }
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

    public function getHeadersRevisaoProperty()
    {
        return [
            ['key' => 'id', 'label' => '#', 'sortable' => true, 'class' => 'w-16 text-center'],
            ['key' => 'candidato', 'label' => 'Candidato e Inscrição', 'sortable' => false],
            ['key' => 'documento', 'label' => 'Documento Exigido', 'sortable' => false],
            ['key' => 'tentativas', 'label' => 'Ações da IA', 'sortable' => false, 'class' => 'text-center'],
            ['key' => 'acoes', 'label' => 'Análise', 'sortable' => false, 'class' => 'text-right w-32'],
        ];
    }

    public function abrirDossie($inscricaoId)
    {
        $this->inscricaoSelecionada = Inscricao::with(['curso', 'unidade'])->findOrFail($inscricaoId);
        
        $this->documentosExigidos = DocumentoExigido::where('ciclo_id', $this->inscricaoSelecionada->ciclo_id)->get();
        $this->documentosEnviados = DocumentoMatricula::where('inscricao_id', $this->inscricaoSelecionada->id)
                                                      ->get()
                                                      ->keyBy('documento_exigido_id');

        $this->motivosReprovacao = []; 
        $this->modalDossieAberto = true;
    }

    public function aprovarDocumento($documentoMatriculaId)
    {
        $doc = DocumentoMatricula::findOrFail($documentoMatriculaId);
        $doc->update(['status_analise' => 'aprovado_manual', 'avaliado_por' => auth()->id()]);
        
        $this->verificarConclusaoMatricula($doc->inscricao_id);
        
        $this->abrirDossie($doc->inscricao_id);
        $this->dispatch('sucesso', msg: 'Documento aprovado manualmente!');
    }

    public function reprovarDocumento($documentoMatriculaId)
    {
        $this->validate([
            "motivosReprovacao.$documentoMatriculaId" => 'required|min:5'
        ], [
            "motivosReprovacao.$documentoMatriculaId.required" => 'Escreva o motivo da recusa para o candidato.',
            "motivosReprovacao.$documentoMatriculaId.min" => 'O motivo deve ser mais descritivo.'
        ]);

        $doc = DocumentoMatricula::findOrFail($documentoMatriculaId);
        
        if (Storage::disk('local')->exists($doc->arquivo_caminho)) {
            Storage::disk('local')->delete($doc->arquivo_caminho);
        }

        $doc->update([
            'status_analise' => 'pendente',
            'tentativas_ia' => 0,
            'avaliado_por' => auth()->id(),
            'log_ia' => array_merge(
                is_array($doc->log_ia) ? $doc->log_ia : [], 
                ['motivo_rejeicao_humana' => $this->motivosReprovacao[$documentoMatriculaId]]
            )
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
        // 1. QUERY DOSSIÊS (Aba 1)
        $queryDossies = Inscricao::with(['curso', 'ciclo', 'unidade'])
            ->whereNotNull('token_matricula')
            ->apenasVinculosPermitidos();

        if (!empty($this->filtroBusca)) {
            $queryDossies->where(function ($q) {
                if (is_numeric($this->filtroBusca)) {
                    $q->where('id', $this->filtroBusca)
                      ->orWhere('cpf', 'like', '%' . $this->filtroBusca . '%');
                } else {
                    $q->where('nome', 'ilike', '%' . $this->filtroBusca . '%')
                      ->orWhere('cpf', 'like', '%' . $this->filtroBusca . '%');
                }
            });
        }
        
        if (!empty($this->filtroCurso)) $queryDossies->where('curso_id', $this->filtroCurso);
        if (!empty($this->filtroUnidade)) $queryDossies->where('unidade_id', $this->filtroUnidade);
        if (!empty($this->filtroTurno)) $queryDossies->where('turno_id', $this->filtroTurno);
        if (!empty($this->filtroEtapa)) $queryDossies->where('etapa_atual', $this->filtroEtapa);

        if ($this->ordenacaoCampo && $this->abaAtiva === 'dossies') {
            $queryDossies->orderBy($this->ordenacaoCampo, $this->ordenacaoDirecao);
        } else {
            $queryDossies->orderBy('updated_at', 'desc');
        }

        // 2. QUERY REVISÃO DA IA (Aba 2)
        $queryRevisao = DocumentoMatricula::with(['inscricao.curso', 'inscricao.unidade', 'documentoExigido'])
            ->whereIn('status_analise', ['analise_manual', 'invalido_ia']);
        
        $queryRevisao->whereHas('inscricao', function($q) {
            $q->apenasVinculosPermitidos();

            if (!empty($this->filtroBusca)) {
                $q->where(function ($sub) {
                    if (is_numeric($this->filtroBusca)) {
                        $sub->where('id', $this->filtroBusca)
                            ->orWhere('cpf', 'like', '%' . $this->filtroBusca . '%');
                    } else {
                        $sub->where('nome', 'ilike', '%' . $this->filtroBusca . '%')
                            ->orWhere('cpf', 'like', '%' . $this->filtroBusca . '%');
                    }
                });
            }
            if (!empty($this->filtroCurso)) $q->where('curso_id', $this->filtroCurso);
            if (!empty($this->filtroUnidade)) $q->where('unidade_id', $this->filtroUnidade);
            if (!empty($this->filtroTurno)) $q->where('turno_id', $this->filtroTurno);
            if (!empty($this->filtroEtapa)) $q->where('etapa_atual', $this->filtroEtapa);
        });

        if ($this->ordenacaoCampoRevisao && $this->abaAtiva === 'revisao') {
            $queryRevisao->orderBy($this->ordenacaoCampoRevisao, $this->ordenacaoDirecaoRevisao);
        } else {
            $queryRevisao->orderBy('updated_at', 'asc');
        }

        return view('livewire.matricula.processo-matricula-manager', [
            'registros' => $queryDossies->paginate($this->porPagina),
            'revisoes' => $queryRevisao->paginate($this->porPagina, ['*'], 'revisaoPage'),
            'totalRevisoes' => (clone $queryRevisao)->count(),
            'cursosDb' => Curso::whereIn('status', ['Ativo', 'ativo', '1', 1, true])->orderBy('nome')->get(),
            'unidadesDb' => Unidade::whereIn('status', ['Ativa', '1', true])->orderBy('nome')->get(),
            'turnosDb' => Turno::orderBy('nome')->get(),
        ]);
    }
}