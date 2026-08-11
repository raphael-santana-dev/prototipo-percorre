<?php

namespace App\Modules\Auditoria\UI\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\AuditoriaLog;
use App\Traits\ComPadraoListagem;
use App\Helpers\BreadcrumbHelper;

class AuditoriaManager extends Component
{
    use WithPagination;
    use ComPadraoListagem;

    public array $breadcrumbs = [];

    public function mount()
    {
        abort_if(!auth()->user()->hasRole('dev|admin'), 403);
        $this->breadcrumbs = BreadcrumbHelper::generate();
    }

    public function getHeadersProperty()
    {
        return [
            ['key' => 'id', 'label' => '#ID', 'sortable' => false, 'class' => 'w-16'],
            ['key' => 'created_at', 'label' => 'Data / Hora', 'sortable' => false],
            ['key' => 'usuario_nome', 'label' => 'Usuário / IP', 'sortable' => false],
            ['key' => 'acao', 'label' => 'Ação', 'sortable' => false, 'class' => 'text-center'],
            ['key' => 'tabela_alterada', 'label' => 'Tabela / Reg. ID', 'sortable' => false],
            ['key' => 'acoes', 'label' => '', 'sortable' => false, 'class' => 'w-16 text-right'], // <- NOVA COLUNA
        ];
    }

    public function showQuickView(int $id)
    {
        $log = AuditoriaLog::findOrFail($id);

        // 1. Badge Dinâmica de Ação
        $acaoBadge = match(strtolower($log->acao)) {
            'criacao' => '<span class="bg-green-100 text-green-700 px-3 py-1 rounded text-[11px] font-bold uppercase tracking-wider border border-green-200"><i class="ph-bold ph-plus"></i> Criação</span>',
            'atualizacao' => '<span class="bg-blue-100 text-blue-700 px-3 py-1 rounded text-[11px] font-bold uppercase tracking-wider border border-blue-200"><i class="ph-bold ph-pencil-simple"></i> Atualização</span>',
            'exclusao' => '<span class="bg-red-100 text-red-700 px-3 py-1 rounded text-[11px] font-bold uppercase tracking-wider border border-red-200"><i class="ph-bold ph-trash"></i> Exclusão</span>',
            default => '<span class="bg-gray-100 text-gray-700 px-3 py-1 rounded text-[11px] font-bold uppercase tracking-wider border border-gray-200">'.$log->acao.'</span>',
        };

        // 2. Info do Usuário
        $infoUsuario = "
            <div class='text-sm text-gray-700 dark:text-gray-300 space-y-1'>
                <p><b>Nome:</b> {$log->usuario_nome} ({$log->usuario_role})</p>
                <p><b>Login:</b> {$log->usuario_login}</p>
                <p><b>IP de Origem:</b> {$log->ip}</p>
                <div class='mt-2 p-2 bg-gray-50 dark:bg-gray-800 rounded border border-gray-100 dark:border-gray-700 text-xs text-gray-500 break-words'>
                    <b>Navegador/Device:</b><br> {$log->navegador}
                </div>
            </div>
        ";

        // 3. Info do Sistema
        $dataAlteracao = $log->created_at ? $log->created_at->format('d/m/Y \à\s H:i:s') : 'N/A';
        $infoSistema = "
            <div class='text-sm text-gray-700 dark:text-gray-300 space-y-1'>
                <p><b>Tabela Afetada:</b> <span class='font-mono text-purpura-600'>{$log->tabela_alterada}</span></p>
                <p><b>ID do Registro:</b> {$log->registro_id}</p>
                <p><b>Momento da Ação:</b> {$dataAlteracao}</p>
            </div>
        ";

        // 4. Construtor de Comparativo (Diff)
        $oldData = is_string($log->informacao_anterior) ? json_decode($log->informacao_anterior, true) : ($log->informacao_anterior ?? []);
        $newData = is_string($log->nova_informacao) ? json_decode($log->nova_informacao, true) : ($log->nova_informacao ?? []);

        $comparativo = '';
        
        if (empty($oldData) && empty($newData)) {
            $comparativo = '<span class="text-gray-500 text-sm italic">Nenhum dado detalhado foi registrado neste log.</span>';
        } else {
            $comparativo .= '<div class="overflow-x-auto max-h-[350px] custom-scrollbar rounded-lg border border-gray-200 dark:border-gray-700"><table class="min-w-full text-left text-[11px]">';
            $comparativo .= '<thead class="bg-gray-100 dark:bg-gray-900 text-gray-600 dark:text-gray-400 font-bold uppercase tracking-wider sticky top-0 shadow-sm">';
            $comparativo .= '<tr><th class="p-3 border-r border-gray-200 dark:border-gray-700">Campo (Coluna)</th><th class="p-3 border-r border-gray-200 dark:border-gray-700">Valor Anterior</th><th class="p-3">Novo Valor</th></tr></thead><tbody class="divide-y divide-gray-100 dark:divide-gray-800">';
            
            // Une todas as chaves (colunas) que existem no array velho e no novo e ordena alfabeticamente
            $allKeys = array_unique(array_merge(array_keys($oldData ?? []), array_keys($newData ?? [])));
            sort($allKeys);

            foreach ($allKeys as $key) {
                // Ignora timestamps padrão se não foram alterados para não poluir a tela
                if (in_array($key, ['created_at', 'updated_at', 'deleted_at']) && ($oldData[$key] ?? '') === ($newData[$key] ?? '')) {
                    continue;
                }

                $oldVal = $oldData[$key] ?? null;
                $newVal = $newData[$key] ?? null;
                
                // Formatação para Arrays/Objetos e Booleanos
                $formatValue = function($val) {
                    if (is_null($val)) return '<span class="text-gray-400 italic">null</span>';
                    if (is_bool($val)) return $val ? 'true' : 'false';
                    if (is_array($val) || is_object($val)) return json_encode($val, JSON_UNESCAPED_UNICODE);
                    return htmlspecialchars((string) $val);
                };

                $oldValStr = $formatValue($oldVal);
                $newValStr = $formatValue($newVal);
                
                $isDifferent = $oldVal !== $newVal;
                
                // Definição de Cores baseado no status da alteração
                $rowClass = $isDifferent ? 'bg-yellow-50/30 dark:bg-yellow-900/10' : 'bg-white dark:bg-gray-800';
                $newValClass = $isDifferent ? 'text-green-600 font-bold bg-green-50 dark:bg-green-900/20 px-1 rounded' : 'text-gray-600 dark:text-gray-300';
                $oldValClass = $isDifferent ? 'text-red-500 line-through bg-red-50 dark:bg-red-900/20 px-1 rounded' : 'text-gray-600 dark:text-gray-300';
                
                // Ajustes finos para Criação e Exclusão
                if (strtolower($log->acao) === 'criacao') {
                    $oldValStr = '-';
                    $oldValClass = 'text-gray-400 dark:text-gray-600';
                    $newValClass = 'text-green-600 font-medium dark:text-green-400';
                } elseif (strtolower($log->acao) === 'exclusao') {
                    $newValStr = '-';
                    $newValClass = 'text-gray-400 dark:text-gray-600';
                    $oldValClass = 'text-red-500 font-medium dark:text-red-400';
                }

                $comparativo .= "<tr class='hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors {$rowClass}'>";
                $comparativo .= "<td class='p-3 border-r border-gray-100 dark:border-gray-700 font-bold text-gray-700 dark:text-gray-300 w-1/4 break-words'>{$key}</td>";
                $comparativo .= "<td class='p-3 border-r border-gray-100 dark:border-gray-700 break-words w-2/5'><span class='{$oldValClass}'>{$oldValStr}</span></td>";
                $comparativo .= "<td class='p-3 break-words w-2/5'><span class='{$newValClass}'>{$newValStr}</span></td>";
                $comparativo .= "</tr>";
            }
            $comparativo .= '</tbody></table></div>';
        }

        // 5. Dispara o componente QuickViewDrawer global
        $this->dispatch('load-quick-view', [
            'title' => 'Log de Registro #' . $log->id,
            'subtitle' => 'Auditoria disparada na tabela ' . $log->tabela_alterada,
            'icon' => 'ph-file-search',
            'data' => [
                'Tipo de Operação' => $acaoBadge,
                'Referência do Sistema' => $infoSistema,
                'Rastreabilidade de Rede' => $infoUsuario,
                'Payload (Dados Trafegados)' => $comparativo,
            ]
        ]);
    }

    public function render()
    {
        $query = AuditoriaLog::query()->with('usuario');

        if ($this->ordenacaoCampo) {
            $query->orderBy($this->ordenacaoCampo, $this->ordenacaoDirecao);
        } else {
            $query->orderBy('id', 'desc');
        }

        return view('livewire.auditoria.auditoria-manager', [
            'registros' => $query->paginate($this->porPagina)
        ])->layout('components.layouts.app', ['title' => 'Auditoria de Sistema']);
    }
}