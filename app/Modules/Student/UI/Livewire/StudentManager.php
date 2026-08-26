<?php

namespace App\Modules\Student\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Modules\Student\Domain\Models\Student;
use App\Modules\Unidade\Domain\Models\Unidade;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

use Livewire\WithPagination;
use App\Helpers\BreadcrumbHelper;
use App\Traits\ComPadraoListagem;
use App\Traits\WithToggleStatus;

#[Layout('components.layouts.app')]
#[Title('Gerenciar Estudantes - Administrativo')]
class StudentManager extends Component
{
    use WithPagination;
    use ComPadraoListagem;
    use WithToggleStatus;

    public bool $showModal = false;
    public bool $isEditMode = false;
    public ?int $studentId = null;

    public string $name = '';
    public string $email = '';
    public string $password = '';
    public bool $is_active = true;
    public ?int $unidade_id = null;

    public $is_aprendiz = false;
    public $empresa_id = null;

    public string $statusColumn = 'is_active';
    public $modelClass = Student::class;

    public array $breadcrumbs = [];

    public $filtro_busca = '';
    public $filtro_unidade = '';
    public $filtro_status = '';

    public function mount()
    {
        abort_if(!feature('estudante.listar'), 403, 'Módulo de estudantes desativado.');
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('estudante.listar'), 403, 'Você não tem permissão para listar alunos.');

        $this->breadcrumbs = BreadcrumbHelper::generate();

        $this->permiteGrid = true;
    }

    public function openModal()
    {
        abort_if(!feature('estudante.criar'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('estudante.criar'), 403);

        $this->resetInputFields();
        $this->showModal = true;
    }

    public function updating($nomePropriedade)
    {
        if (in_array($nomePropriedade, ['filtro_busca', 'filtro_unidade', 'filtro_status'])) {
            $this->resetPage();
        }
    }

    public function limparFiltros()
    {
        $this->reset(['filtro_busca', 'filtro_unidade', 'filtro_status']);
        $this->resetPage();
    }

    public function edit(int $id)
    {
        abort_if(!feature('estudante.editar'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('estudante.editar'), 403);

        $this->resetInputFields();
        
        $student = Student::findOrFail($id);
        $this->studentId = $student->id;
        $this->name = $student->name;
        $this->email = $student->email;
        $this->is_active = $student->is_active;
        $this->unidade_id = $student->unidade_id;

        // Carrega dados de Aprendizagem
        $this->is_aprendiz = $student->is_aprendiz;
        $this->empresa_id = $student->empresa_id;
        
        $this->isEditMode = true;
        $this->showModal = true;
    }

    public function save()
    {
        if ($this->isEditMode) {
            abort_if(!feature('estudante.editar'), 403);
            abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('estudante.editar'), 403);
        } else {
            abort_if(!feature('estudante.criar'), 403);
            abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('estudante.criar'), 403);
        }

        $rules = [
            'name' => 'required|string|min:3|max:255',
            'email' => 'required|email|unique:students,email' . ($this->studentId ? ',' . $this->studentId : ''),
            'unidade_id' => 'required|exists:unidades,id',
            'empresa_id' => 'required_if:is_aprendiz,true', // Validação Condicional
        ];

        if (!$this->isEditMode) {
            $rules['password'] = 'required|string|min:6';
        } elseif (!empty($this->password)) {
            $rules['password'] = 'string|min:6';
        }

        $this->validate($rules, [
            'empresa_id.required_if' => 'Selecione uma empresa para vincular o aprendiz.'
        ]);

        $data = [
            'name' => $this->name,
            'email' => strtolower($this->email),
            'is_active' => $this->is_active,
            'unidade_id' => $this->unidade_id,
            'slug' => Str::slug($this->name),
            'is_aprendiz' => $this->is_aprendiz,
            'empresa_id' => $this->is_aprendiz ? $this->empresa_id : null, // Se não for aprendiz, remove a empresa
        ];

        if (!empty($this->password)) {
            $data['password'] = Hash::make($this->password);
        }

        if ($this->isEditMode) {
            Student::findOrFail($this->studentId)->update($data);
        } else {
            Student::create($data);
        }

        $this->showModal = false;
        $this->resetInputFields();
        $this->dispatch('sucesso', msg: 'Estudante salvo com sucesso!');
    }

    public function delete(int $id)
    {
        abort_if(!feature('estudante.excluir'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('estudante.excluir'), 403);

        Student::findOrFail($id)->delete();
        $this->dispatch('sucesso', msg: 'Estudante excluído com sucesso!');
    }

    public function getHeadersProperty()
    {
        return [
            ['key' => 'id', 'label' => 'ID', 'sortable' => true],
            ['key' => 'name', 'label' => 'Aluno', 'sortable' => true],
            ['key' => 'unidade_nome', 'label' => 'Unidade', 'sortable' => true],
            ['key' => 'is_active', 'label' => 'Status', 'sortable' => true],
            ['key' => 'acoes', 'label' => 'Ações', 'sortable' => false, 'class' => 'text-right'],
        ];
    }

    public function toggleStatus($id)
    {
        abort_if(!feature('estudante.editar'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('estudante.editar'), 403);
        $this->traitToggleStatus($id);
    }

    public function showQuickDetails(int $id)
    {
        abort_if(!feature('estudante.visualizar'), 403);
        abort_if(!auth()->user()->hasRole('dev') && !auth()->user()->can('estudante.visualizar'), 403);

        $student = Student::with(['unidade', 'empresa'])->findOrFail($id);
        
        $statusHtml = $student->is_active 
            ? '<span class="text-green-600 font-bold">Matrícula Ativa</span>' 
            : '<span class="text-red-600 font-bold">Matrícula Inativa</span>';

        $detalhes = [
            'Nome do Aluno' => $student->name,
            'E-mail de Acesso' => $student->email,
            'Unidade Sede' => $student->unidade?->nome ?? 'Não alocado',
            'Programa Aprendiz' => $student->is_aprendiz ? '<span class="text-indigo-600 font-bold">' . ($student->empresa?->nome_fantasia ?? 'Sem Empresa') . '</span>' : 'Não',
            'Status' => $statusHtml,
            'Data da Matrícula' => $student->created_at->format('d/m/Y H:i'),
        ];

        $this->dispatch('load-quick-view', [
            'title' => 'Ficha Rápida do Aluno', 
            'icon' => 'ph-graduation-cap', 
            'data' => $detalhes,
            'subtitle' => 'Resumo cadastral'
        ]);
    }

    private function resetInputFields()
    {
        $this->studentId = null;
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->is_active = true;
        $this->unidade_id = auth()->user()->unidades->first()->id ?? null;
        
        // Limpa os campos de aprendizagem
        $this->is_aprendiz = false;
        $this->empresa_id = null;

        $this->isEditMode = false;
        $this->resetErrorBag();
    }

    public function render()
    {
        $query = Student::query()->with(['unidade', 'empresa'])->apenasVinculosPermitidos();

        $query->when($this->filtro_busca, function($q) {
            $q->where(function($sub) {
                $sub->where('name', 'ilike', '%' . $this->filtro_busca . '%')
                    ->orWhere('email', 'ilike', '%' . $this->filtro_busca . '%');
            });
        })
        ->when($this->filtro_unidade, fn($q) => $q->where('unidade_id', $this->filtro_unidade))
        ->when($this->filtro_status !== '', fn($q) => $q->where('is_active', $this->filtro_status));

        // Aplica a ordenação no banco de dados
        if ($this->ordenacaoCampo) {
            
            // LÓGICA ESPECIAL PARA RELACIONAMENTO
            if ($this->ordenacaoCampo === 'unidade_nome') {
                $query->orderBy(
                    \App\Modules\Unidade\Domain\Models\Unidade::select('nome')
                        ->whereColumn('unidades.id', 'students.unidade_id'),
                    $this->ordenacaoDirecao
                );
            } else {
                // Ordenação padrão para colunas da própria tabela (name, id, is_active)
                $query->orderBy($this->ordenacaoCampo, $this->ordenacaoDirecao);
            }

        } else {
            $query->orderBy('id', 'desc');
        }

        $estudantes = $query->paginate($this->porPagina);

        // Busca todas as empresas ativas para o dropdown do Modal
        $empresas = \App\Modules\Company\Domain\Models\Empresa::where('is_active', true)
                  ->orderBy('nome_fantasia')
                  ->get();

        return view('livewire.student.student-manager', [
            'registros' => $estudantes,
            'unidades' => Unidade::orderBy('nome')->get(),
            'empresas' => $empresas,
        ]);
    }
}