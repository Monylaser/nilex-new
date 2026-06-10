<button {{ $attributes->merge([
    'type' => 'submit',
    'class' => 'inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-nilex border border-transparent rounded-xl font-bold text-sm text-white transition-all duration-200 hover:bg-nilex-dark active:scale-95 focus:outline-none focus:ring-2 focus:ring-nilex focus:ring-offset-2 disabled:opacity-60 disabled:cursor-not-allowed',
]) }}>
    {{ $slot }}
</button>
