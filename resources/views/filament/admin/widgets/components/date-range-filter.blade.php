<x-filament::input.wrapper
    inline-prefix
    wire:target="filter"
    class="fi-wi-chart-filter"
>
    <x-filament::input.select
        inline-prefix
        wire:model.live="filter"
    >
        @foreach ($filters as $value => $label)
            <option value="{{ $value }}">
                {{ $label }}
            </option>
        @endforeach
    </x-filament::input.select>
</x-filament::input.wrapper>
