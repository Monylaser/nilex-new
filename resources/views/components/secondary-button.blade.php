<button {{ $attributes->merge([
    'type' => 'button',
    'class' => 'inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-white border border-zinc-200 rounded-xl font-bold text-sm text-zinc-700 shadow-card transition-all duration-200 hover:bg-zinc-50 hover:border-zinc-300 active:scale-95 focus:outline-none focus:ring-2 focus:ring-nilex focus:ring-offset-2 disabled:opacity-60 disabled:cursor-not-allowed',
]) }}>
    {{ $slot }}
</button>
