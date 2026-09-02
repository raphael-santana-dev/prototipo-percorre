<?php

namespace App\Modules\Period\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Ciclo;
use App\Models\Curso;
use App\Models\StatusInscricao;
use App\Models\OfertaVaga;
use App\Modules\Matricula\Domain\Models\DocumentoExigido;
use App\Modules\Matricula\Domain\Models\DocumentoMatricula;

#[Layout('components.layouts.app')]
#[Title('Editar Ciclo - Administrativo')]
class PeriodEdit extends Component
{
    public $cicloId, $nome, $ano, $semestre, $data_inicio, $data_fim, $status;
    
    public array $unidadesSelecionadas = []; 
    public array $cursosSelecionados = []; 
    public array $turnosSelecionados = []; 
    
    public array $statusSelecionados = [];
    public $novoStatusSelecionado = ''; 

    public array $ofertasVagas = [];
    public array $documentosExigidos = []; // NOVA VARIÁVEL

    public $activeUnidadeId = null;
    public $activeCursoId = null;

    public function mount($id)
    {
        abort_if(!feature('ciclo.editar'), 403, 'Edição de ciclos desativada.');
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('ciclo.editar'), 403);

        $ciclo = Ciclo::with(['unidades', 'cursos', 'turnos', 'statusPipeline'])->findOrFail($id);
        
        $this->cicloId = $ciclo->id;
        $this->nome = $ciclo->nome;
        $this->ano = $ciclo->ano;
        $this->semestre = $ciclo->semestre;
        $this->data_inicio = $ciclo->data_inicio->format('Y-m-d\TH:i');
        $this->data_fim = $ciclo->data_fim->format('Y-m-d\TH:i');
        $this->status = $ciclo->status;
        
        $this->unidadesSelecionadas = $ciclo->unidades->pluck('id')->map(fn($v) => (string)$v)->toArray();
        $this->cursosSelecionados = $ciclo->cursos->pluck('id')->map(fn($v) => (string)$v)->toArray();
        $this->turnosSelecionados = $ciclo->turnos->pluck('id')->map(fn($v) => (string)$v)->toArray();
        
        $statusOrdenados = $ciclo->statusPipeline->sortBy(function($status) { return $status->pivot->ordem ?? 999; });
        $this->statusSelecionados = $statusOrdenados->pluck('id')->map(fn($v) => (string)$v)->toArray();

        $ofertas = OfertaVaga::where('ciclo_id', $id)->get();
        foreach ($ofertas as $oferta) {
            $this->ofertasVagas[] = [
                'unidade_id' => $oferta->unidade_id, 'curso_id' => $oferta->curso_id, 'turno_id' => $oferta->turno_id,
                'vagas' => $oferta->vagas, 'idade_min' => $oferta->idade_min, 'idade_max' => $oferta->idade_max,
            ];
        }

        // CARREGA OS DOCUMENTOS EXIGIDOS DO CICLO
        $docs = DocumentoExigido::where('ciclo_id', $id)->get();
        foreach ($docs as $doc) {
            $this->documentosExigidos[] = [
                'id' => $doc->id, 'nome' => $doc->nome, 'descricao' => $doc->descricao, 'is_obrigatorio' => (bool) $doc->is_obrigatorio
            ];
        }
    }

    public function setActiveUnidade($id) { $this->activeUnidadeId = $id; $this->activeCursoId = null; }
    public function setActiveCurso($id) { $this->activeCursoId = $id; }

    public function updatedOfertasVagas($value, $name)
    {
        $parts = explode('.', $name);
        if (count($parts) === 2) {
            $index = $parts[0]; $campo = $parts[1];
            if ($campo === 'unidade_id') { $this->ofertasVagas[$index]['curso_id'] = ''; $this->ofertasVagas[$index]['turno_id'] = ''; } 
            elseif ($campo === 'curso_id') { $this->ofertasVagas[$index]['turno_id'] = ''; }
        }
    }

    public function adicionarStatusPipeline()
    {
        if (!empty($this->novoStatusSelecionado) && !in_array($this->novoStatusSelecionado, $this->statusSelecionados)) {
            $this->statusSelecionados[] = $this->novoStatusSelecionado;
        }
        $this->novoStatusSelecionado = '';
    }

    public function removerStatusPipeline($id) { $this->statusSelecionados = array_values(array_diff($this->statusSelecionados, [$id])); }
    public function atualizarOrdemStatus($ordemIds) { $this->statusSelecionados = $ordemIds; }

    public function addOferta() { $this->ofertasVagas[] = ['unidade_id' => '', 'curso_id' => '', 'turno_id' => '', 'vagas' => 0, 'idade_min' => null, 'idade_max' => null]; }
    public function removeOferta($index) { unset($this->ofertasVagas[$index]); $this->ofertasVagas = array_values($this->ofertasVagas); }

    // --- MÉTODOS DE DOCUMENTOS EXIGIDOS ---
    public function addDocumento() {
        $this->documentosExigidos[] = ['id' => null, 'nome' => '', 'descricao' => '', 'is_obrigatorio' => true];
    }

    public function removeDocumento($index) {
        $docId = $this->documentosExigidos[$index]['id'] ?? null;
        if ($docId) {
            $emUso = DocumentoMatricula::where('documento_exigido_id', $docId)->exists();
            if ($emUso) {
                $this->dispatch('erro', msg: 'Ação Bloqueada: Candidatos já enviaram este documento. Excluí-lo corromperia o histórico da matrícula.');
                return;
            }
            DocumentoExigido::find($docId)->delete();
        }
        unset($this->documentosExigidos[$index]);
        $this->documentosExigidos = array_values($this->documentosExigidos);
    }

    public function salvar()
    {
        abort_if(!feature('ciclo.editar'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('ciclo.editar'), 403);
        
        $this->validate([
            'nome' => 'nullable|string|max:255', 'ano' => 'required|integer', 'semestre' => 'required|integer',
            'data_inicio' => 'required|date', 'data_fim' => 'required|date',
            'documentosExigidos.*.nome' => 'required|string|max:255'
        ], ['documentosExigidos.*.nome.required' => 'O nome do documento é obrigatório.']);

        if ($this->status) { Ciclo::where('id', '!=', $this->cicloId)->update(['status' => false]); }

        $cicloSalvo = Ciclo::findOrFail($this->cicloId);
        $cicloSalvo->update([
            'nome' => empty(trim($this->nome)) ? "{$this->ano} - {$this->semestre}º Semestre" : $this->nome,
            'ano' => $this->ano, 'semestre' => $this->semestre, 'data_inicio' => $this->data_inicio,
            'data_fim' => $this->data_fim, 'status' => $this->status,
        ]);

        $cicloSalvo->unidades()->sync($this->unidadesSelecionadas);
        $cicloSalvo->cursos()->sync($this->cursosSelecionados);
        $cicloSalvo->turnos()->sync($this->turnosSelecionados);

        $syncStatus = [];
        foreach ($this->statusSelecionados as $index => $statusId) { $syncStatus[$statusId] = ['ordem' => $index + 1]; }
        $cicloSalvo->statusPipeline()->sync($syncStatus);

        OfertaVaga::where('ciclo_id', $cicloSalvo->id)->delete();
        foreach ($this->ofertasVagas as $oferta) {
            if (!empty($oferta['curso_id']) && !empty($oferta['unidade_id']) && !empty($oferta['turno_id'])) {
                OfertaVaga::create([
                    'ciclo_id' => $cicloSalvo->id, 'unidade_id' => $oferta['unidade_id'], 'curso_id' => $oferta['curso_id'],
                    'turno_id' => $oferta['turno_id'], 'vagas' => (int) ($oferta['vagas'] ?? 0),
                    'idade_min' => !empty($oferta['idade_min']) ? (int) $oferta['idade_min'] : null,
                    'idade_max' => !empty($oferta['idade_max']) ? (int) $oferta['idade_max'] : null,
                ]);
            }
        }

        // SALVA OS DOCUMENTOS DO CICLO
        foreach ($this->documentosExigidos as $docData) {
            if (!empty(trim($docData['nome']))) {
                DocumentoExigido::updateOrCreate(
                    ['id' => $docData['id'], 'ciclo_id' => $cicloSalvo->id],
                    ['nome' => trim($docData['nome']), 'descricao' => $docData['descricao'] ?? '', 'is_obrigatorio' => $docData['is_obrigatorio'] ?? false]
                );
            }
        }

        session()->flash('sucesso', 'Ciclo, funil e documentos exigidos foram atualizados!');
        return redirect()->route('ciclos.index');
    }

    public function render()
    {
        return view('livewire.period.period-edit', [
            'unidadesDb' => \App\Modules\Unidade\Domain\Models\Unidade::whereIn('status', ['Ativa', '1', true])->orderBy('nome')->get(),
            'cursosDb' => Curso::with(['unidades', 'turnosVinculados'])->whereIn('status', ['Ativo', '1', true])->orderBy('nome')->get(),
            'statusDisponiveis' => StatusInscricao::orderBy('nome')->get(),
        ]);
    }
}