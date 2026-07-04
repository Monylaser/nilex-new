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
        class="fixed inset-0 z-[200] bg-black/70 backdrop-blur-sm flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
        aria-label="إعلان"
        data-ad-id="{{ $popupCampaign->id }}"
    >
        {{-- Modal card --}}
        <div class="w-full max-w-2xl max-h-[85vh] rounded-2xl overflow-hidden shadow-2xl flex flex-col">

            {{-- Upper section: image --}}
            <div class="flex-1 relative overflow-hidden">

                {{-- X close button — top-right (RTL: visual top-left) --}}
                <button type="button"
                        @click="close()"
                        :disabled="!skipEnabled"
                        :class="skipEnabled ? 'hover:bg-black/60 cursor-pointer' : 'cursor-not-allowed opacity-60'"
                        class="absolute top-3 right-3 z-10 bg-black/40 text-white rounded-full w-8 h-8 flex items-center justify-center transition-colors duration-200"
                        aria-label="إغلاق">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                {{-- Ad image (wrapped in link when target URL exists) --}}
                @if($popupTargetUrl)
                    <a href="{{ route('ads.click', $popupCampaign) }}"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="block w-full h-full">
                        <img src="{{ $popupCampaign->getFirstMediaUrl('ad_image', 'tablet') }}"
                             alt="{{ $popupCampaign->title }}"
                             class="w-full h-full object-cover min-h-[300px] max-h-[500px]">
                    </a>
                @else
                    <img src="{{ $popupCampaign->getFirstMediaUrl('ad_image', 'tablet') }}"
                         alt="{{ $popupCampaign->title }}"
                         class="w-full h-full object-cover min-h-[300px] max-h-[500px]">
                @endif
            </div>

            {{-- Lower section: skip bar --}}
            <div class="bg-white px-6 py-4 flex items-center justify-between">

                {{-- Right: countdown label (hidden once timer hits 0) --}}
                <p class="text-sm text-zinc-500" x-show="!skipEnabled">
                    تخطي بعد
                    <span class="font-bold" x-text="countdown"></span>
                    ثوانٍ
                </p>
                {{-- Spacer when countdown hidden so skip button stays left-aligned --}}
                <span x-show="skipEnabled"></span>

                {{-- Left: skip button --}}
                <button type="button"
                        @click="close()"
                        :disabled="!skipEnabled"
                        :class="skipEnabled
                            ? 'text-nilex-teal font-bold cursor-pointer'
                            : 'text-zinc-300 cursor-not-allowed'"
                        class="text-sm transition-colors duration-200">
                    تخطي ←
                </button>
            </div>
        </div>
    </div>
@endif
