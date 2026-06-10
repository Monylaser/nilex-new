@props(['active'])

@php
$classes = ($active ?? false)
    ? 'inline-flex items-center gap-1.5 px-1 py-1 border-b-2 border-nilex text-sm font-bold text-nilex focus:outline-none transition-colors duration-200'
    : 'inline-flex items-center gap-1.5 px-1 py-1 border-b-2 border-transparent text-sm font-medium text-zinc-600 hover:text-nilex hover:border-nilex/30 focus:outline-none transition-colors duration-200';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
