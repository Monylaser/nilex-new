<a {{ $attributes->merge([
    'class' => 'flex items-center gap-2.5 w-full px-4 py-2.5 text-sm text-zinc-700 hover:bg-zinc-50 hover:text-nilex transition-colors duration-150 cursor-pointer',
]) }}>
    {{ $slot }}
</a>
