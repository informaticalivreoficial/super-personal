@props(['label' => '', 'error' => null])

<div class="flex items-center gap-3">
    <button
        type="button"
        role="switch"
        x-data="{ on: @entangle($attributes->wire('model')) }"
        x-on:click="on = !on"
        :aria-checked="on.toString()"
        :class="on ? 'bg-teal-600' : 'bg-gray-200'"
        class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-teal-400 focus:ring-offset-2"
    >
        <span
            :class="on ? 'translate-x-6' : 'translate-x-1'"
            class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
        ></span>
    </button>
    @if($label)
        <span class="text-sm font-medium text-gray-700">{{ $label }}</span>
    @endif
    @if($error)
        <span class="text-red-500 text-xs mt-1 block">{{ $error }}</span>
    @endif
</div>