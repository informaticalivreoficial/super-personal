@props(['label' => '', 'error' => null])

<div>
    @if($label)
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
    @endif
    <select
        {{ $attributes->merge(['class' => 'w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-teal-400 bg-white']) }}
    >
        {{ $slot }}
    </select>
    @if($error)
        <span class="text-red-500 text-xs mt-1 block">{{ $error }}</span>
    @endif
</div>