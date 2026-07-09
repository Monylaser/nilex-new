@php
    $popupCampaign = app(\App\Services\AdCampaignService::class)->getForPlacement('popup');
    $popupImageUrl = $popupCampaign?->getFirstMediaUrl('ad_image', 'desktop') 
                  ?: $popupCampaign?->getFirstMediaUrl('ad_image');
    $popupTargetUrl = $popupCampaign?->target_url;
@endphp

@if($popupCampaign && $popupImageUrl)
<div
    x-data="{
        open: true,
        seconds: 5,
        canSkip: false,
        timer: null,
        init() {
            const key = 'popup_last_seen';
            const last = localStorage.getItem(key);
            const day = 24 * 60 * 60 * 1000;
            if (last && (Date.now() - parseInt(last)) < day) {
                this.open = false;
                return;
            }
            this.timer = setInterval(() => {
                this.seconds--;
                if (this.seconds <= 0) {
                    this.seconds = 0;
                    this.canSkip = true;
                    clearInterval(this.timer);
                }
            }, 1000);
        },
        close() {
            if (!this.canSkip) return;
            clearInterval(this.timer);
            this.open = false;
            localStorage.setItem('popup_last_seen', Date.now().toString());
        }
    }"
    x-show="open"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    style="display:none"
    class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/70 backdrop-blur-sm p-4"
    data-ad-id="{{ $popupCampaign->id }}"
>
    {{-- Modal Card --}}
    <div class="relative w-full max-w-8xl rounded-2xl overflow-hidden shadow-2xl" style="max-height:95vh;">

        {{-- X Button --}}
        <button
            type="button"
            @click="close()"
            :class="canSkip ? 'bg-black/50 hover:bg-black/70 cursor-pointer' : 'bg-black/20 cursor-not-allowed'"
            class="absolute top-3 right-3 z-10 text-white rounded-full w-9 h-9 flex items-center justify-center transition-colors"
            aria-label="{{ __('ui.ad_popup.close') }}"
        >✕</button>

        {{-- Image --}}
        @if($popupTargetUrl)
            <a href="{{ route('ads.click', $popupCampaign) }}" target="_blank" rel="noopener" class="block w-full h-full">
                <img src="{{ $popupImageUrl }}" alt="" class="w-full h-full object-cover" style="max-height:70vh;">
            </a>
        @else
            <img src="{{ $popupImageUrl }}" alt="" class="w-full h-full object-cover" style="max-height:70vh;">
        @endif

        {{-- Skip Bar --}}
        <div class="bg-white px-6 py-4 flex items-center justify-between">
            <button
                type="button"
                @click="close()"
                :class="canSkip ? 'text-[#14A5A8] font-bold cursor-pointer hover:underline' : 'text-zinc-300 cursor-not-allowed'"
                class="text-sm transition-colors"
                x-text="canSkip ? '{{ __('ui.ad_popup.skip_ready') }}' : '{{ __('ui.ad_popup.skip') }}'"
            ></button>
            <span x-show="!canSkip" class="text-sm text-zinc-400" x-text="'{{ __('ui.ad_popup.countdown') }}'.replace(':seconds', seconds)"></span>
        </div>
    </div>
</div>
@endif