{{-- resources/views/components/input/money.blade.php --}}
@props(['label' => '', 'error' => null])

<div>
    @if($label)
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
    @endif
    <div class="relative">
        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">R$</span>
        <input
            {{ $attributes->merge(['class' => 'w-full border border-gray-200 rounded-lg pl-9 pr-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-teal-400']) }}
            type="text"
            inputmode="decimal"
        >
    </div>
    @if($error)
        <span class="text-red-500 text-xs mt-1 block">{{ $error }}</span>
    @endif
</div>