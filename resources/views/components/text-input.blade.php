@props(['disabled' => false])

<input
    @disabled($disabled)
    {{ $attributes->merge([
        'class' => 'w-full px-4 py-3 bg-white border border-zinc-200 rounded-xl text-zinc-900 placeholder-zinc-400 text-sm transition-all duration-200 focus:outline-none focus:border-nilex focus:ring-2 focus:ring-nilex/20 disabled:bg-zinc-50 disabled:text-zinc-400 disabled:cursor-not-allowed',
    ]) }}
>
