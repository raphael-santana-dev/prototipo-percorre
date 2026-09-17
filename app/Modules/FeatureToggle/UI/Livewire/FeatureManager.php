<?php

namespace App\Modules\FeatureToggle\UI\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Modules\FeatureToggle\Domain\Models\Feature;
use App\Modules\FeatureToggle\Application\Services\FeatureService;
use Livewire\WithPagination;
use App\Helpers\BreadcrumbHelper;
use App\Traits\ComPadraoListagem;
use App\Traits\WithToggleStatus;
use Illuminate\Support\Facades\Cache;
use App\Modules\ACL\Domain\Models\Permission;

#[Layout('components.layouts.app')]
#[Title('Gerenciar Features - Administrativo')]
class FeatureManager extends Component
{
    use WithPagination, ComPadraoListagem, WithToggleStatus;

    public $modalAberto = false;
    public $modalConfirmacaoAberto = false;
    public $featureId = null;

    public $modelClass = Feature::class;
    public array $breadcrumbs = [];

    public $filtro_module = '';
    public $filtro_keyword = '';
    public $filtro_status = '';

    public array $items = [];
    public bool $replicar_para_permissoes = false;
    public array $pendenciasReplicacao = [];

    public function mount()
    {
        abort_if(!auth()->user()->hasRole('dev'), 403);
        $this->breadcrumbs = BreadcrumbHelper::generate();
        $this->permiteGrid = true;
    }

    public function updating($nomePropriedade)
    {
        if (in_array($nomePropriedade, ['filtro_module', 'filtro_keyword', 'filtro_status'])) {
            $this->resetPage();
        }
    }

    public function limparFiltros()
    {
        $this->reset(['filtro_module', 'filtro_keyword', 'filtro_status']);
        $this->resetPage();
    }

    public function abrirModal($id = null)
    {
        $this->resetValidation();
        $this->reset(['featureId', 'items', 'replicar_para_permissoes', 'modalConfirmacaoAberto', 'pendenciasReplicacao']);

        if ($id) {
            $feature = Feature::findOrFail($id);
            $this->featureId = $feature->id;
            
            $nameParts = explode('.', $feature->name, 2);
            $this->items[0] = [
                'module' => $feature->module,
                'action' => $nameParts[1] ?? $feature->name,
                'description' => $feature->description
            ];
        } else {
            $this->items = [['module' => '', 'action' => '', 'description' => '']];
        }

        $this->modalAberto = true;
    }

    public function addItem()
    {
        $this->items[] = ['module' => '', 'action' => '', 'description' => ''];
    }

    public function removeItem(int $index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function fecharModal()
    {
        $this->modalAberto = false;
        $this->modalConfirmacaoAberto = false;
    }

    public function salvar()
    {
        $this->validate([
            'items.*.module' => 'required|string|min:2',
            'items.*.action' => 'required|string|min:2',
            'items.*.description' => 'required|string|max:255',
        ], [
            'items.*.module.required' => 'Módulo é obrigatório.',
            'items.*.action.required' => 'Ação é obrigatória.',
            'items.*.description.required' => 'Descrição é obrigatória.',
        ]);

        $this->pendenciasReplicacao = [];

        if ($this->replicar_para_permissoes) {
            foreach ($this->items as $item) {
                $fullName = strtolower(trim($item['module'])) . '.' . strtolower(trim($item['action']));
                $correspondenciaEncontrada = false;

                // Se for edição, verifica se o nome ANTIGO existe na tabela espelho
                if ($this->featureId) {
                    $featureAntiga = Feature::find($this->featureId);
                    if ($featureAntiga) {
                        $nomeAntigo = $featureAntiga->getOriginal('name');
                        if (Permission::where('name', $nomeAntigo)->exists()) {
                            $correspondenciaEncontrada = true;
                        }
                    }
                }

                // Só lança para a pendência se não achou nem o antigo e nem o novo
                if (!$correspondenciaEncontrada && !Permission::where('name', $fullName)->exists()) {
                    $this->pendenciasReplicacao[] = $fullName;
                }
            }
        }

        if (!empty($this->pendenciasReplicacao)) {
            $this->modalConfirmacaoAberto = true;
            return;
        }

        $this->efetivarSalvar(true);
    }

    public function confirmarCriacaoAusentes()
    {
        $this->modalConfirmacaoAberto = false;
        $this->efetivarSalvar(true);
    }

    public function ignorarCriacaoAusentes()
    {
        $this->modalConfirmacaoAberto = false;
        $this->efetivarSalvar(false);
    }

    private function efetivarSalvar(bool $criarAusentes = true)
    {
        $featureService = app(FeatureService::class);

        foreach ($this->items as $index => $item) {
            $moduleFinal = strtolower(trim($item['module']));
            $actionFinal = strtolower(trim($item['action']));
            $fullName = $moduleFinal . '.' . $actionFinal;
            $nomeAntigo = null;

            // ATUALIZA OU CRIA A FEATURE
            if ($this->featureId) {
                if (Feature::where('name', $fullName)->where('id', '!=', $this->featureId)->exists()) {
                    $this->addError("items.{$index}.action", 'Esta feature já está cadastrada.');
                    return;
                }

                $feature = Feature::findOrFail($this->featureId);
                $nomeAntigo = $feature->getOriginal('name');
                
                $feature->update([
                    'module' => $moduleFinal,
                    'name' => $fullName,
                    'description' => $item['description']
                ]);

                Cache::forget("feature_status_{$nomeAntigo}");
                Cache::forget("feature_status_{$fullName}");
            } else {
                if (Feature::where('name', $fullName)->exists()) {
                    $this->addError("items.{$index}.action", "A feature {$fullName} já existe.");
                    continue; 
                }
                $featureService->create($moduleFinal, $fullName, $item['description']);
            }

            // ATUALIZA OU CRIA O ESPELHO NO ACL (PERMISSÃO)
            if ($this->replicar_para_permissoes) {
                $permissionTarget = null;
                
                // Tenta achar a permissão pelo nome antigo (caso seja uma edição)
                if ($nomeAntigo) {
                    $permissionTarget = Permission::where('name', $nomeAntigo)->first();
                }
                
                // Se não achou pelo antigo, tenta achar pelo nome novo
                if (!$permissionTarget) {
                    $permissionTarget = Permission::where('name', $fullName)->first();
                }
                
                if ($permissionTarget) {
                    $permissionTarget->update([
                        'name' => $fullName,
                        'module' => $moduleFinal,
                        'description' => $item['description']
                    ]);
                } elseif ($criarAusentes) {
                    Permission::create([
                        'name' => $fullName,
                        'guard_name' => 'web',
                        'module' => $moduleFinal,
                        'description' => $item['description']
                    ]);
                }
            }
        }

        $this->fecharModal();
        $this->dispatch('sucesso', msg: $this->featureId ? 'Feature e Permissão atualizadas!' : 'Cadastros realizados com sucesso!');
    }

    public function excluir($id)
    {
        $feature = Feature::findOrFail($id);
        $featureName = $feature->name;
        $feature->delete();
        
        Cache::forget("feature_status_{$featureName}");
        $this->dispatch('sucesso', msg: 'Feature excluída com sucesso.');
    }

    public function toggleStatus($id)
    {
        $feature = Feature::findOrFail($id);
        $service = app(FeatureService::class);
        $service->toggle($feature->name, !$feature->is_active);
        $this->dispatch('sucesso', msg: 'Status da feature alterado!');
    }

    public function getHeadersProperty()
    {
        return [
            ['key' => 'id', 'label' => 'ID', 'sortable' => true],
            ['key' => 'module', 'label' => 'Módulo', 'sortable' => true],
            ['key' => 'name', 'label' => 'Feature', 'sortable' => true],
            ['key' => 'description', 'label' => 'Descrição', 'sortable' => true],
            ['key' => 'is_active', 'label' => 'Status', 'sortable' => true],
            ['key' => 'acoes', 'label' => 'Ações', 'sortable' => false, 'class' => 'text-right'],
        ];
    }

    public function render()
    {
        $query = Feature::query()
            ->when($this->filtro_module, fn($q) => $q->where('module', $this->filtro_module))
            ->when($this->filtro_status !== '', fn($q) => $q->where('is_active', $this->filtro_status))
            ->when($this->filtro_keyword, function($q) {
                $q->where(function($subQ) {
                    $subQ->where('name', 'like', "%{$this->filtro_keyword}%")
                         ->orWhere('description', 'like', "%{$this->filtro_keyword}%");
                });
            });

        if ($this->ordenacaoCampo) {
            $query->orderBy($this->ordenacaoCampo, $this->ordenacaoDirecao);
        } else {
            $query->orderBy('module', 'asc')->orderBy('name', 'asc');
        }

        return view('livewire.feature-toggle.feature-manager', [
            'registros' => $query->paginate($this->porPagina),
            'modulosDisponiveis' => Feature::select('module')->distinct()->orderBy('module')->pluck('module')
        ]);
    }
}