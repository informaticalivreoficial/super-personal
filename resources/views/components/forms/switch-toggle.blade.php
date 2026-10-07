<div>
    @props([
        'checked' => false,
        'size' => 'md',
        'color' => 'green',
        'disabled' => false,
    ])

    @php
        $sizes = [
            'sm' => [
                'track' => 'w-9 h-5 mt-2',
                'thumb' => 'after:h-4 after:w-4 after:top-[2px]',
            ],
            'md' => [
                'track' => 'w-11 h-6 mt-2',
                'thumb' => 'after:h-5 after:w-5 after:top-0.5',
            ],
            'lg' => [
                'track' => 'w-14 h-7 mt-2',
                'thumb' => 'after:h-6 after:w-6 after:top-0.5',
            ],
        ];

        $colors = [
            'green' => 'peer-checked:bg-green-600',
            'blue'  => 'peer-checked:bg-blue-600',
            'red'   => 'peer-checked:bg-red-600',
        ];
    @endphp

    <label class="relative inline-flex items-center cursor-pointer {{ $disabled ? 'opacity-50 cursor-not-allowed' : '' }}">
        <input
            type="checkbox"
            class="sr-only peer"
            @checked($checked)
            @disabled($disabled)
            {{ $attributes->except(['class']) }}
        >

        <div class="
            relative bg-gray-300 rounded-full transition
            {{ $sizes[$size]['track'] }}
            {{ $colors[$color] ?? $colors['green'] }}
            after:content-['']
            after:absolute after:left-[2px]
            after:bg-white after:rounded-full
            after:transition-all
            peer-checked:after:translate-x-full
            {{ $sizes[$size]['thumb'] }}
        ">
        </div>
    </label>
</div>