<aside class="w-64 xl:w-72 bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800 flex flex-col shrink-0 h-full overflow-hidden relative z-20">
    <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between shrink-0">
        <span class="text-xs font-semibold text-gray-800 dark:text-gray-200">
            Tipos de Campos
        </span>
        @if($campoId)
            <button type="button" wire:click="iniciarNovoCampo" class="text-xs font-medium text-purpura-600 hover:underline">
                + Novo
            </button>
        @endif
    </div>

    <div class="p-3.5 overflow-y-auto flex-1 custom-scrollbar space-y-5">
        @php
            $gruposPaleta = [
                'Texto e Documentos' => [
                    ['tipo' => 'text', 'sub' => 'text',  'icon' => 'ph-text-t',               'label' => 'Texto Curto', 'cols' => 1],
                    ['tipo' => 'text', 'sub' => 'email', 'icon' => 'ph-envelope-simple',      'label' => 'E-mail',      'cols' => 1],
                    ['tipo' => 'text', 'sub' => 'number','icon' => 'ph-hash',                 'label' => 'Número',      'cols' => 1],
                    ['tipo' => 'text', 'sub' => 'money', 'icon' => 'ph-currency-circle-dollar','label' => 'Moeda (R$)',  'cols' => 1],
                    ['tipo' => 'text', 'sub' => 'tel',   'icon' => 'ph-device-mobile',        'label' => 'Telefone',    'cols' => 1],
                    ['tipo' => 'text', 'sub' => 'cpf',   'icon' => 'ph-identification-card',  'label' => 'CPF',         'cols' => 1],
                    ['tipo' => 'text', 'sub' => 'cnpj',  'icon' => 'ph-buildings',            'label' => 'CNPJ',        'cols' => 1],
                    ['tipo' => 'text', 'sub' => 'cep',   'icon' => 'ph-map-pin-line',         'label' => 'CEP (ViaCEP)','cols' => 1],
                ],
                'Datas e Horas' => [
                    ['tipo' => 'text', 'sub' => 'date',           'icon' => 'ph-calendar-blank', 'label' => 'Data',                'cols' => 1],
                    ['tipo' => 'text', 'sub' => 'time',           'icon' => 'ph-clock',          'label' => 'Hora',                'cols' => 1],
                    ['tipo' => 'text', 'sub' => 'datetime-local', 'icon' => 'ph-calendar-check', 'label' => 'Data e Hora Completa','cols' => 2],
                ],
                'Anexos e Assinatura' => [
                    ['tipo' => 'signature', 'sub' => 'usuario', 'icon' => 'ph-signature',     'label' => 'Assinatura à Mão', 'cols' => 2],
                    ['tipo' => 'file',      'sub' => 'file',    'icon' => 'ph-upload-simple', 'label' => 'Upload / Anexos',  'cols' => 2],
                ],
                'Seleção e Escolhas' => [
                    ['tipo' => 'select', 'sub' => 'text', 'icon' => 'ph-caret-circle-down',   'label' => 'Dropdown',     'cols' => 1],
                    ['tipo' => 'radio',  'sub' => 'text', 'icon' => 'ph-radio-button',        'label' => 'Múltipla Esc.','cols' => 1],
                    ['tipo' => 'check',  'sub' => 'text', 'icon' => 'ph-check-square-offset', 'label' => 'Checkboxes',   'cols' => 1],
                    ['tipo' => 'scale',  'sub' => 'text', 'icon' => 'ph-linear-scale',        'label' => 'Escala Num.',  'cols' => 1],
                    ['tipo' => 'matriz', 'sub' => 'text', 'icon' => 'ph-table',               'label' => 'Matriz',       'cols' => 1],
                    ['tipo' => 'rating', 'sub' => 'text', 'icon' => 'ph-star',                'label' => 'Estrelas',     'cols' => 1],
                ],
                'Links e Layout' => [
                    ['tipo' => 'link',    'sub' => 'link',   'icon' => 'ph-link-simple',                'label' => 'Link ou Documento (PDF/Office)', 'cols' => 2],
                    ['tipo' => 'html',    'sub' => 'p',      'icon' => 'ph-article',                    'label' => 'Texto / Aviso',                  'cols' => 1],
                    ['tipo' => 'media',   'sub' => 'image',  'icon' => 'ph-image',                      'label' => 'Mídia Visual',                   'cols' => 1],
                    ['tipo' => 'social',  'sub' => 'text',   'icon' => 'ph-share-network',              'label' => 'Redes Sociais',                  'cols' => 1],
                    ['tipo' => 'divider', 'sub' => 'text',   'icon' => 'ph-minus',                      'label' => 'Divisória',                      'cols' => 1],
                    ['tipo' => 'spacer',  'sub' => 'spacer', 'icon' => 'ph-arrows-out-line-horizontal', 'label' => 'Espaço em Branco (Colunas)',     'cols' => 2],
                ],
                'Integração Acadêmica' => [
                    ['tipo' => 'system', 'sub' => 'unidade',         'icon' => 'ph-buildings',      'label' => 'Unidades Ativas',                  'cols' => 2],
                    ['tipo' => 'system', 'sub' => 'curso',           'icon' => 'ph-graduation-cap', 'label' => 'Cursos Ativos',                    'cols' => 2],
                    ['tipo' => 'system', 'sub' => 'turno',           'icon' => 'ph-clock',          'label' => 'Turnos Ativos',                    'cols' => 2],
                    ['tipo' => 'system', 'sub' => 'combo_academico', 'icon' => 'ph-tree-structure', 'label' => 'Conjunto Unidade / Curso / Turno', 'cols' => 2],
                ],
            ];
        @endphp

        @foreach($gruposPaleta as $tituloGrupo => $itens)
            <div>
                <p class="text-[11px] font-medium text-gray-400 mb-2">{{ $tituloGrupo }}</p>
                <div class="grid grid-cols-2 gap-1.5">
                    @foreach($itens as $item)
                        @php
                            $ativo = $painelCampoAtivo && $tipo === $item['tipo'] && ($item['tipo'] !== 'text' && $item['tipo'] !== 'system' || $subtipo === $item['sub']);
                        @endphp
                        <button type="button"
                                wire:click="setTipo('{{ $item['tipo'] }}', '{{ $item['sub'] }}')"
                                class="{{ $item['cols'] === 2 ? 'col-span-2' : 'col-span-1' }} flex items-center gap-2 p-2 border rounded-lg text-left transition {{ $ativo ? 'border-gray-900 bg-gray-100 text-gray-900 dark:border-white dark:bg-gray-800 dark:text-white font-semibold' : 'border-gray-200 dark:border-gray-800 hover:border-gray-300 dark:hover:border-gray-700 text-gray-700 dark:text-gray-300 font-medium' }}">
                            <i class="ph {{ $item['icon'] }} text-sm text-gray-500 shrink-0"></i>
                            <span class="text-xs truncate">{{ $item['label'] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</aside>