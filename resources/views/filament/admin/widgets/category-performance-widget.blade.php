<x-filament-widgets::widget class="fi-wi-table">
    <x-filament::section
        :collapsible="false"
    >
        <x-slot name="afterHeader">
            <x-filament::input.wrapper
                inline-prefix
                wire:target="filter"
                class="fi-wi-chart-filter"
            >
                <x-filament::input.select
                    inline-prefix
                    wire:model.live="filter"
                >
                    @foreach ($this->getFilters() as $value => $label)
                        <option value="{{ $value }}">
                            {{ $label }}
                        </option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </x-slot>

        {{ $this->table }}
    </x-filament::section>
</x-filament-widgets::widget>
