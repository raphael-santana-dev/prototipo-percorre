@props(['status', 'action'])

<button wire:click="{{ $action }}" type="button"
        class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-brand-purple focus:ring-offset-2 {{ $status ? 'bg-green-500' : 'bg-gray-300' }}">
    <span class="sr-only">Alternar status</span>
    <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $status ? 'translate-x-5' : 'translate-x-0' }}"></span>
</button>