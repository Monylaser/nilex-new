@php
    $popupCampaign = app(\App\Services\AdCampaignService::class)->getForPlacement('popup');
    $popupImageUrl = $popupCampaign?->getFirstMediaUrl('ad_image');
    $popupTargetUrl = $popupCampaign?->target_url;
@endphp

@if($popupCampaign && $popupImageUrl)
    <div
        x-data="{
            open: false,
            countdown: 5,
            skipEnabled: false,
            _timer: null,
            storageKey: 'popup_last_seen',
            dayMs: 86400000,
            init() {
                var lastSeen = localStorage.getItem(this.storageKey);
                if (lastSeen && (Date.now() - parseInt(lastSeen, 10)) < this.dayMs) {
                    return;
                }
                this.open = true;
                this.trackImpression();
                this.startCountdown();
            },
            startCountdown() {
                var self = this;
                self.countdown = 5;
                self.skipEnabled = false;
                self._timer = setInterval(function () {
                    self.countdown -= 1;
                    if (self.countdown <= 0) {
                        clearInterval(self._timer);
                        self._timer = null;
                        self.skipEnabled = true;
                    }
                }, 1000);
            },
            close() {
                if (!this.skipEnabled) return;
                if (this._timer) { clearInterval(this._timer); this._timer = null; }
                localStorage.setItem(this.storageKey, String(Date.now()));
                this.open = false;
            },
            trackImpression() {
                fetch(@json(route('ads.impression', $popupCampaign)), {
                    method: 'GET',
                    headers: {'X-Requested-With': 'XMLHttpRequest'},
                }).catch(function () {});
            }
        }"
        x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-cloak
        class="fixed inset-0 z-[200] bg-black/70 backdrop-blur-sm"
        role="dialog"
        aria-modal="true"
        aria-label="إعلان"
        data-ad-id="{{ $popupCampaign->id }}"
    >
        {{-- Full-screen image --}}
        @if($popupTargetUrl)
            <a href="{{ route('ads.click', $popupCampaign) }}"
               target="_blank"
               rel="noopener noreferrer"
               class="absolute inset-0 block">
                <img src="{{ $popupCampaign->getFirstMediaUrl('ad_image', 'tablet') }}"
                     alt="{{ $popupCampaign->title }}"
                     class="w-full h-full object-cover">
            </a>
        @else
            <img src="{{ $popupCampaign->getFirstMediaUrl('ad_image', 'tablet') }}"
                 alt="{{ $popupCampaign->title }}"
                 class="absolute inset-0 w-full h-full object-cover">
        @endif

        {{-- Skip controls — absolute, bottom-centre, on top of the image --}}
        <div class="absolute bottom-8 inset-x-0 z-10 flex flex-col items-center gap-3 pointer-events-none">

            {{-- Countdown label (separate element, no @click) --}}
            <p class="text-white/80 text-sm drop-shadow pointer-events-none"
               x-show="!skipEnabled">
                تخطي بعد
                <span class="font-bold" x-text="countdown"></span>
                ثوانٍ
            </p>

            {{-- Skip button — has @click, does NOT have x-show --}}
            <button type="button"
                    @click="close()"
                    :disabled="!skipEnabled"
                    :class="skipEnabled
                        ? 'bg-white text-gray-900 shadow-lg cursor-pointer hover:bg-white/90 active:scale-95'
                        : 'bg-white/20 text-white/40 cursor-not-allowed'"
                    class="pointer-events-auto px-8 py-2.5 rounded-full text-sm font-bold transition-all duration-200">
                {{-- These spans carry x-show, the button itself does not --}}
                <span x-show="!skipEnabled" x-cloak>تخطي</span>
                <span x-show="skipEnabled">تخطي ←</span>
            </button>
        </div>
    </div>
@endif
