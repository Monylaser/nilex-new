{{-- resources/views/filament/modals/ai-container.blade.php --}}
<div class="p-2">
    @livewire('smart-ad-creator', ['existingImages' => $currentImages ?? []])
</div>