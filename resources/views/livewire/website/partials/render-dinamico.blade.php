{{-- SAFELIST: Força o Tailwind a compilar essas classes de largura (Não apague esta div) --}}
<div class="hidden col-span-3 col-span-4 col-span-6 col-span-12 md:col-span-3 md:col-span-4 md:col-span-6 md:col-span-12"></div>

@foreach($camposDinamicos->where('etapa', $etapaAtual) as $campo)
    @php
        $isCondicional = !empty($campo->depende_de) && !empty($campo->depende_valor);
        
        $listaOpcoes = [];
        if (!empty($campo->opcoes)) {
            if (is_array($campo->opcoes)) {
                $listaOpcoes = $campo->opcoes;
            } else {
                $decodificado = json_decode($campo->opcoes, true);
                $listaOpcoes = is_array($decodificado) ? $decodificado : explode(',', $campo->opcoes);
            }
        }

        // Aplica o tamanho 12 para mobile, e o tamanho escolhido no painel para telas médias (computador)
        $colSpan = "col-span-12 md:col-span-{$campo->largura}";
    @endphp

    <div class="{{ $colSpan }}"
        @if($isCondicional)
            x-cloak
            x-show="window.avaliarCondicao(
                ($wire.respostas && $wire.respostas['{{ $campo->depende_de }}']) || $wire.{{ $campo->depende_de }},
                '{{ $campo->depende_operador }}',
                '{{ $campo->depende_valor }}'
            )"
        @endif
    >
        <label class="block text-sm font-semibold text-brand-textLabel mb-1">
            {{ $campo->label }} 
            @if($campo->obrigatorio) <span class="text-red-500">*</span> @endif
        </label>
        
        @if($campo->tipo === 'select')
            <select wire:model.live="respostas.{{ $campo->name }}" class="w-full rounded-md border border-brand-border px-3 py-2 focus:ring-brand-purple focus:border-brand-purple @error('respostas.'.$campo->name) border-red-500 @enderror">
                <option value="">Selecione...</option>
                @foreach($listaOpcoes as $opcao)
                    <option value="{{ trim($opcao) }}">{{ trim($opcao) }}</option>
                @endforeach
            </select>
            
        @elseif($campo->tipo === 'radio')
            <div class="flex flex-wrap gap-4 mt-2">
                @foreach($listaOpcoes as $opcao)
                    <label class="inline-flex items-center">
                        <input wire:model.live="respostas.{{ $campo->name }}" type="radio" value="{{ trim($opcao) }}" class="form-radio text-brand-purple focus:ring-brand-purple">
                        <span class="ml-2 text-sm text-gray-700">{{ trim($opcao) }}</span>
                    </label>
                @endforeach
            </div>

        @else
            <input type="{{ $campo->subtipo ?? 'text' }}" 
                   wire:model.live.debounce.500ms="respostas.{{ $campo->name }}" 
                   
                   @if($campo->regex_mascara)
                        x-mask="{{ $campo->regex_mascara }}"
                   @endif
                   
                   @if($campo->tamanho_min && $campo->subtipo == 'number') min="{{ $campo->tamanho_min }}" @endif
                   @if($campo->tamanho_max && $campo->subtipo == 'number') max="{{ $campo->tamanho_max }}" @endif

                   class="w-full rounded-md border border-brand-border px-3 py-2 focus:ring-brand-purple focus:border-brand-purple @error('respostas.'.$campo->name) border-red-500 @enderror">
        @endif

        @error('respostas.'.$campo->name) <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
    </div>
@endforeach

@once
<script>
    window.avaliarCondicao = function(valorAtual, operador, valorEsperado) {
        if(valorAtual === undefined || valorAtual === null) valorAtual = '';
        
        let val = String(valorAtual).toLowerCase().trim();
        let target = String(valorEsperado).toLowerCase().trim();
        
        let numVal = Number(val);
        let numTarget = Number(target);

        switch(operador) {
            case '=': return val === target;
            case '!=': return val !== target;
            case '>': return !isNaN(numVal) && !isNaN(numTarget) && numVal > numTarget;
            case '<': return !isNaN(numVal) && !isNaN(numTarget) && numVal < numTarget;
            case '>=': return !isNaN(numVal) && !isNaN(numTarget) && numVal >= numTarget;
            case '<=': return !isNaN(numVal) && !isNaN(numTarget) && numVal <= numTarget;
            case 'in': return target.split(',').map(s => s.trim()).includes(val);
            default: return false;
        }
    }
</script>
@endonce