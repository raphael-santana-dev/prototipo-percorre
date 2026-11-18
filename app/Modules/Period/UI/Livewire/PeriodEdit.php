<?php

namespace App\Modules\Period\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Ciclo;
use App\Models\Curso;
use App\Models\StatusInscricao;
use App\Models\OfertaVaga;
use App\Modules\Unidade\Domain\Models\Unidade;
use App\Modules\Turno\Domain\Models\Turno;
use App\Modules\Matricula\Domain\Models\DocumentoExigido;
use App\Modules\Matricula\Domain\Models\DocumentoMatricula;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Traits\WithEditingLock;

#[Layout('components.layouts.app')]
#[Title('Editar Ciclo - Administrativo')]
class PeriodEdit extends Component
{
    use WithEditingLock;

    public $cicloId, $nome, $ano, $semestre, $data_inicio, $data_fim, $status;
    public $data_inicio_matricula = '';
    public $data_fim_matricula = '';

    public string $tipo_ciclo = 'regular';
    public ?string $slug_projeto = null;

    // Chaves independentes:
    // $unidadesSelecionadas = ['1', '2']
    // $cursosSelecionados   = ['1-3', '2-5'] ("{unidadeId}-{cursoId}")
    // $turnosSelecionados   = ['1-3-1', '1-3-2'] ("{unidadeId}-{cursoId}-{turnoId}")
    public array $unidadesSelecionadas = []; 
    public array $cursosSelecionados = []; 
    public array $turnosSelecionados = []; 
    
    public array $statusSelecionados = [];
    public $novoStatusSelecionado = ''; 

    public array $ofertasVagas = [];
    public array $documentosExigidos = []; 

    public $activeUnidadeId = null;
    public $activeCursoId = null;

    public function mount($id)
    {
        $ciclo = Ciclo::with(['unidades', 'cursos', 'turnos', 'statusPipeline'])->findOrFail($id);
        
        $this->cicloId = $ciclo->id;
        $this->nome = $ciclo->nome;
        $this->tipo_ciclo = $ciclo->tipo_ciclo ?? 'regular';
        $this->slug_projeto = $ciclo->slug_projeto;
        $this->ano = $ciclo->ano;
        $this->semestre = $ciclo->semestre;
        $this->data_inicio = $ciclo->data_inicio->format('Y-m-d\TH:i');
        $this->data_fim = $ciclo->data_fim->format('Y-m-d\TH:i');
        $this->status = (bool) $ciclo->status;
        $this->data_inicio_matricula = $ciclo->data_inicio_matricula ? \Carbon\Carbon::parse($ciclo->data_inicio_matricula)->format('Y-m-d\TH:i') : '';
        $this->data_fim_matricula = $ciclo->data_fim_matricula ? \Carbon\Carbon::parse($ciclo->data_fim_matricula)->format('Y-m-d\TH:i') : '';
        
        $this->unidadesSelecionadas = $ciclo->unidades->pluck('id')->map(fn($v) => (string) $v)->toArray();

        $statusOrdenados = $ciclo->statusPipeline->sortBy(fn($s) => $s->pivot->ordem ?? 999);
        $this->statusSelecionados = $statusOrdenados->pluck('id')->map(fn($v) => (string) $v)->toArray();

        $unidadesMap = Unidade::pluck('nome', 'id');
        $cursosMap   = Curso::pluck('nome', 'id');
        $turnosMap   = Turno::pluck('nome', 'id');

        $cursosComp = [];
        $turnosComp = [];

        $ofertas = OfertaVaga::where('ciclo_id', $id)->get();
        foreach ($ofertas as $oferta) {
            $uId = (string) $oferta->unidade_id;
            $cId = (string) $oferta->curso_id;
            $tId = (string) $oferta->turno_id;

            if ($uId !== '' && !in_array($uId, $this->unidadesSelecionadas, true)) {
                $this->unidadesSelecionadas[] = $uId;
            }
            if ($uId !== '' && $cId !== '') {
                $cursosComp[] = "{$uId}-{$cId}";
            }
            if ($uId !== '' && $cId !== '' && $tId !== '') {
                $turnosComp[] = "{$uId}-{$cId}-{$tId}";
            }

            $this->ofertasVagas[] = [
                'unidade_id'   => $uId,
                'unidade_nome' => $unidadesMap[$oferta->unidade_id] ?? 'Unidade',
                'curso_id'     => $cId,
                'curso_nome'   => $cursosMap[$oferta->curso_id] ?? 'Curso',
                'turno_id'     => $tId,
                'turno_nome'   => $turnosMap[$oferta->turno_id] ?? 'Turno',
                'vagas'        => $oferta->vagas, 
                'meta'         => $oferta->meta,
                'idade_min'    => $oferta->idade_min,
                'idade_max'    => $oferta->idade_max,
            ];
        }

        $this->cursosSelecionados = array_values(array_unique($cursosComp));
        $this->turnosSelecionados = array_values(array_unique($turnosComp));

        if (!empty($this->unidadesSelecionadas)) {
            $this->activeUnidadeId = (int) $this->unidadesSelecionadas[0];
            $primeiroCursoDaUnidade = collect($this->cursosSelecionados)
                ->first(fn($c) => str_starts_with($c, "{$this->activeUnidadeId}-"));
            if ($primeiroCursoDaUnidade) {
                [, $cAtivo] = explode('-', $primeiroCursoDaUnidade, 2);
                $this->activeCursoId = (int) $cAtivo;
            }
        }

        $docs = DocumentoExigido::where('ciclo_id', $id)->get();
        foreach ($docs as $doc) {
            $this->documentosExigidos[] = [
                'id'             => $doc->id,
                'nome'           => $doc->nome,
                'descricao'      => $doc->descricao,
                'is_obrigatorio' => (bool) $doc->is_obrigatorio
            ];
        }

        $this->iniciarLockEdicao('ciclo_dados', (int) $this->cicloId, 'Dados do Ciclo', 'ciclos.index');
    }

    public function setActiveUnidade($id): void
    {
        $this->activeUnidadeId = $id;
        $this->activeCursoId = null;
    }

    public function setActiveCurso($id): void
    {
        $this->activeCursoId = $id;
    }

    /**
     * Quando desmarca uma Unidade, remove os cursos e turnos daquela Unidade e ressincroniza as vagas.
     */
    public function updatedUnidadesSelecionadas(): void
    {
        $ativas = array_values(array_unique(array_map('strval', $this->unidadesSelecionadas)));
        $this->unidadesSelecionadas = $ativas;

        $this->cursosSelecionados = array_values(array_filter($this->cursosSelecionados, function ($v) use ($ativas) {
            [$uId] = explode('-', (string) $v, 2);
            return in_array($uId, $ativas, true);
        }));

        $this->turnosSelecionados = array_values(array_filter($this->turnosSelecionados, function ($v) use ($ativas) {
            [$uId] = explode('-', (string) $v, 3);
            return in_array($uId, $ativas, true);
        }));

        $this->sincronizarCombinacoesVagas();
    }

    /**
     * Quando marca/desmarca um Curso ("{uId}-{cId}"), garante a Unidade marcada ou limpa os turnos daquele curso.
     */
    public function updatedCursosSelecionados(): void
    {
        $cursosAtivos = array_values(array_unique(array_map('strval', $this->cursosSelecionados)));
        $this->cursosSelecionados = $cursosAtivos;

        foreach ($cursosAtivos as $cKey) {
            if (str_contains($cKey, '-')) {
                [$uId] = explode('-', $cKey, 2);
                if (!in_array($uId, $this->unidadesSelecionadas, true)) {
                    $this->unidadesSelecionadas[] = $uId;
                }
            }
        }

        $this->turnosSelecionados = array_values(array_filter($this->turnosSelecionados, function ($v) use ($cursosAtivos) {
            $p = explode('-', (string) $v);
            return count($p) === 3 && in_array("{$p[0]}-{$p[1]}", $cursosAtivos, true);
        }));

        $this->sincronizarCombinacoesVagas();
    }

    /**
     * Quando marca/desmarca um Turno ("{uId}-{cId}-{tId}"), marca automaticamente o Curso e a Unidade
     * e atualiza imediatamente a lista de ofertas de vagas.
     */
    public function updatedTurnosSelecionados(): void
    {
        $this->turnosSelecionados = array_values(array_unique(array_map('strval', $this->turnosSelecionados)));

        foreach ($this->turnosSelecionados as $tKey) {
            $p = explode('-', (string) $tKey);
            if (count($p) === 3) {
                [$uId, $cId] = $p;
                $chaveCurso = "{$uId}-{$cId}";
                if (!in_array($uId, $this->unidadesSelecionadas, true)) {
                    $this->unidadesSelecionadas[] = $uId;
                }
                if (!in_array($chaveCurso, $this->cursosSelecionados, true)) {
                    $this->cursosSelecionados[] = $chaveCurso;
                }
            }
        }

        $this->sincronizarCombinacoesVagas();
    }

    public function sincronizarCombinacoesVagas(): void
    {
        $unidadesMap = Unidade::pluck('nome', 'id');
        $cursosMap   = Curso::pluck('nome', 'id');
        $turnosMap   = Turno::pluck('nome', 'id');

        $ofertasExistentes = [];
        foreach ($this->ofertasVagas as $oferta) {
            if (!empty($oferta['unidade_id']) && !empty($oferta['curso_id']) && !empty($oferta['turno_id'])) {
                $key = "{$oferta['unidade_id']}-{$oferta['curso_id']}-{$oferta['turno_id']}";
                $ofertasExistentes[$key] = $oferta;
            }
        }

        $novasOfertas = [];

        foreach ($this->turnosSelecionados as $combo) {
            $parts = explode('-', (string) $combo);
            if (count($parts) === 3) {
                [$uId, $cId, $tId] = $parts;

                if (!in_array($uId, $this->unidadesSelecionadas, true)) continue;
                if (!in_array("{$uId}-{$cId}", $this->cursosSelecionados, true)) continue;

                if (isset($ofertasExistentes[$combo])) {
                    $novasOfertas[] = array_merge($ofertasExistentes[$combo], [
                        'unidade_nome' => $unidadesMap[$uId] ?? 'Unidade',
                        'curso_nome'   => $cursosMap[$cId] ?? 'Curso',
                        'turno_nome'   => $turnosMap[$tId] ?? 'Turno',
                    ]);
                } else {
                    $novasOfertas[] = [
                        'unidade_id'   => (string) $uId,
                        'unidade_nome' => $unidadesMap[$uId] ?? 'Unidade',
                        'curso_id'     => (string) $cId,
                        'curso_nome'   => $cursosMap[$cId] ?? 'Curso',
                        'turno_id'     => (string) $tId,
                        'turno_nome'   => $turnosMap[$tId] ?? 'Turno',
                        'vagas'        => 0,
                        'meta'         => null,
                        'idade_min'    => null,
                        'idade_max'    => null,
                    ];
                }
            }
        }

        $this->ofertasVagas = $novasOfertas;
    }

    public function contarTurnosDoCursoNaUnidade(int $unidadeId, int $cursoId): int
    {
        $prefixo = "{$unidadeId}-{$cursoId}-";
        return count(array_filter($this->turnosSelecionados, fn($v) => str_starts_with((string) $v, $prefixo)));
    }

    public function updatedOfertasVagas($value, $name)
    {
        $parts = explode('.', $name);
        if (count($parts) === 2) {
            $index = $parts[0];
            $campo = $parts[1];
            if ($campo === 'unidade_id') { 
                $this->ofertasVagas[$index]['curso_id'] = ''; 
                $this->ofertasVagas[$index]['turno_id'] = ''; 
            } elseif ($campo === 'curso_id') { 
                $this->ofertasVagas[$index]['turno_id'] = ''; 
            } elseif ($campo === 'vagas' && is_numeric($value)) {
                $this->ofertasVagas[$index]['meta'] = $value * 3;
            }
        }
    }

    public function adicionarStatusPipeline()
    {
        if (!empty($this->novoStatusSelecionado) && !in_array($this->novoStatusSelecionado, $this->statusSelecionados)) {
            $this->statusSelecionados[] = $this->novoStatusSelecionado;
        }
        $this->novoStatusSelecionado = '';
    }

    public function atualizarOrdemStatus($ordemIds) 
    { 
        $this->statusSelecionados = $ordemIds; 
        
        $syncStatus = [];
        foreach ($this->statusSelecionados as $index => $statusId) { $syncStatus[$statusId] = ['ordem' => $index + 1]; }
        Ciclo::findOrFail($this->cicloId)->statusPipeline()->sync($syncStatus);
    }

    public function removerStatusPipeline($id) 
    { 
        $emUso = \App\Models\Inscricao::where('ciclo_id', $this->cicloId)->where('status_inscricao_id', $id)->exists();
        if ($emUso) {
            $this->dispatch('erro', msg: 'Ação Bloqueada: Já existem inscrições neste ciclo vinculadas a este status.');
            return;
        }

        $this->statusSelecionados = array_values(array_diff($this->statusSelecionados, [$id])); 
        
        $syncStatus = [];
        foreach ($this->statusSelecionados as $index => $statusId) { $syncStatus[$statusId] = ['ordem' => $index + 1]; }
        Ciclo::findOrFail($this->cicloId)->statusPipeline()->sync($syncStatus);
    }
    
    public function addOferta()
    {
        $this->ofertasVagas[] = ['unidade_id' => '', 'curso_id' => '', 'turno_id' => '', 'vagas' => 0, 'meta' => null, 'idade_min' => null, 'idade_max' => null];
    }

    public function removeOferta($index)
    {
        $removida = $this->ofertasVagas[$index] ?? null;
        if ($removida && !empty($removida['unidade_id']) && !empty($removida['curso_id']) && !empty($removida['turno_id'])) {
            $comboRemover = "{$removida['unidade_id']}-{$removida['curso_id']}-{$removida['turno_id']}";
            $this->turnosSelecionados = array_values(array_diff($this->turnosSelecionados, [$comboRemover]));
        }

        unset($this->ofertasVagas[$index]);
        $this->ofertasVagas = array_values($this->ofertasVagas);
    }

    public function addDocumento()
    {
        $this->documentosExigidos[] = ['id' => null, 'nome' => '', 'descricao' => '', 'is_obrigatorio' => true];
    }

    public function removeDocumento($index)
    {
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
        if ($this->isLockedByOther) {
            $this->dispatch('erro', msg: "Este ciclo está sendo editado por {$this->lockOwnerName}.");
            return;
        }

        if ($this->tipo_ciclo === 'customizado') {
            $this->slug_projeto = Str::slug($this->slug_projeto ?: $this->nome);
        } else {
            $this->slug_projeto = null;
        }

        $this->validate([
            'nome'         => 'nullable|string|max:255',
            'ano'          => 'required|integer',
            'semestre'     => 'required|integer',
            'data_inicio'  => 'required|date',
            'data_fim'     => 'required|date',
            'tipo_ciclo'   => 'required|in:regular,customizado',
            'slug_projeto' => [
                'nullable',
                'required_if:tipo_ciclo,customizado',
                'regex:/^[a-z0-9\-]+$/',
                Rule::unique('ciclos', 'slug_projeto')->ignore($this->cicloId),
            ],
            'documentosExigidos.*.nome' => 'required|string|max:255'
        ], [
            'documentosExigidos.*.nome.required' => 'O nome do documento é obrigatório.',
            'slug_projeto.required_if'           => 'Para ciclos customizados, informe o slug da URL.',
            'slug_projeto.unique'                => 'Este slug de projeto já está em uso por outro ciclo.',
            'slug_projeto.regex'                 => 'O slug deve conter apenas letras minúsculas, números e hífens.',
        ]);

        if ($this->status && $this->tipo_ciclo === 'regular') {
            Ciclo::where('tipo_ciclo', 'regular')
                ->where('id', '!=', $this->cicloId)
                ->update(['status' => false]);
        }

        $cicloSalvo = Ciclo::findOrFail($this->cicloId);
        $cicloSalvo->update([
            'nome'                  => empty(trim($this->nome)) ? "{$this->ano} - {$this->semestre}º Semestre" : $this->nome,
            'tipo_ciclo'            => $this->tipo_ciclo,
            'slug_projeto'          => $this->tipo_ciclo === 'customizado' ? $this->slug_projeto : null,
            'ano'                   => $this->ano,
            'semestre'              => $this->semestre,
            'data_inicio'           => $this->data_inicio,
            'data_fim'              => $this->data_fim,
            'status'                => $this->status,
            'data_inicio_matricula' => !empty($this->data_inicio_matricula) ? $this->data_inicio_matricula : null,
            'data_fim_matricula'    => !empty($this->data_fim_matricula) ? $this->data_fim_matricula : null,
        ]);

        // Extrai IDs inteiros únicos das chaves compostas ("{uId}-{cId}" e "{uId}-{cId}-{tId}")
        $unidadesUnicas = collect($this->unidadesSelecionadas)->map(fn($v) => (int) $v)->unique()->filter()->values()->toArray();
        $cursosUnicos   = collect($this->cursosSelecionados)->map(fn($v) => (int) (explode('-', (string) $v)[1] ?? $v))->unique()->filter()->values()->toArray();
        $turnosUnicos   = collect($this->turnosSelecionados)->map(fn($v) => (int) (explode('-', (string) $v)[2] ?? $v))->unique()->filter()->values()->toArray();

        $cicloSalvo->unidades()->sync($unidadesUnicas);
        $cicloSalvo->cursos()->sync($cursosUnicos);
        $cicloSalvo->turnos()->sync($turnosUnicos);

        $syncStatus = [];
        foreach ($this->statusSelecionados as $index => $statusId) { $syncStatus[$statusId] = ['ordem' => $index + 1]; }
        $cicloSalvo->statusPipeline()->sync($syncStatus);

        OfertaVaga::where('ciclo_id', $cicloSalvo->id)->delete();
        foreach ($this->ofertasVagas as $oferta) {
            if (!empty($oferta['curso_id']) && !empty($oferta['unidade_id']) && !empty($oferta['turno_id'])) {
                OfertaVaga::create([
                    'ciclo_id'   => $cicloSalvo->id,
                    'unidade_id' => (int) $oferta['unidade_id'],
                    'curso_id'   => (int) $oferta['curso_id'],
                    'turno_id'   => (int) $oferta['turno_id'],
                    'vagas'      => (int) ($oferta['vagas'] ?? 0),
                    'meta'       => !empty($oferta['meta']) ? (int) $oferta['meta'] : null,
                    'idade_min'  => !empty($oferta['idade_min']) ? (int) $oferta['idade_min'] : null,
                    'idade_max'  => !empty($oferta['idade_max']) ? (int) $oferta['idade_max'] : null,
                ]);
            }
        }

        foreach ($this->documentosExigidos as $docData) {
            if (!empty(trim($docData['nome']))) {
                DocumentoExigido::updateOrCreate(
                    ['id' => $docData['id'], 'ciclo_id' => $cicloSalvo->id],
                    ['nome' => trim($docData['nome']), 'descricao' => $docData['descricao'] ?? '', 'is_obrigatorio' => $docData['is_obrigatorio'] ?? false]
                );
            }
        }

        $this->liberarLockEdicao();
        session()->flash('sucesso', 'Ciclo, funil e documentos exigidos foram atualizados!');
        return redirect()->route('ciclos.index');
    }

    public function render()
    {
        return view('livewire.period.period-edit', [
            'unidadesDb'        => Unidade::whereIn('status', ['Ativa', '1', true])->orderBy('nome')->get(),
            'cursosDb'          => Curso::with(['unidades', 'turnosVinculados'])->whereIn('status', ['Ativo', '1', true])->orderBy('nome')->get(),
            'statusDisponiveis' => StatusInscricao::orderBy('nome')->get(),
        ]);
    }
}