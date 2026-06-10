@props(['active'])

@php
$classes = ($active ?? false)
    ? 'flex items-center gap-3 w-full px-4 py-3 rounded-xl text-sm font-bold text-nilex bg-nilex/5 focus:outline-none transition-colors duration-150'
    : 'flex items-center gap-3 w-full px-4 py-3 rounded-xl text-sm font-medium text-zinc-700 hover:bg-zinc-50 hover:text-nilex focus:outline-none transition-colors duration-150';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
