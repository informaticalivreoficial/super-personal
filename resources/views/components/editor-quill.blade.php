@props([
    'value' => '',
    'model',
])

<div
    wire:ignore
    x-data="quillEditor({
        value: @js($value),
        model: '{{ $model }}'
    })"
    x-init="init()"
    class="bg-white"
>
    <div x-ref="editor" style="min-height:300px"></div>
</div>
