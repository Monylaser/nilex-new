<button {{ $attributes->merge([
    'type' => 'submit',
    'class' => 'btn-nilex-primary gap-2 px-5 py-2.5 rounded-xl text-sm border border-transparent disabled:opacity-60 disabled:cursor-not-allowed',
]) }}>
    {{ $slot }}
</button>
