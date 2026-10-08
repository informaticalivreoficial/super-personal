{{-- Classe final: a do chamador, senão o padrão (evita merge com classes
     conflitantes tipo h-4/h-5, em que venceria a ordem do CSS). --}}
@include('components.icons.'.$name, [
    'svgClass' => $attributes->has('class') ? $attributes->get('class') : 'h-5 w-5',
])
