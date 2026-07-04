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
            storageKey: 'popup_last_seen',
            dayMs: 86400000,
            init() {
                var lastSeen = localStorage.getItem(this.storageKey);

                if (lastSeen && (Date.now() - parseInt(lastSeen, 10)) < this.dayMs) {
                    return;
                }

                this.open = true;
                localStorage.setItem(this.storageKey, String(Date.now()));
                this.trackImpression();
                this.startCountdown();
            },
            startCountdown() {
                var self = this;
                self.countdown = 5;
                self.skipEnabled = false;

                var timer = setInterval(function () {
                    self.countdown -= 1;

                    if (self.countdown <= 0) {
                        clearInterval(timer);
                        self.skipEnabled = true;
                    }
                }, 1000);
            },
            close() {
                if (!this.skipEnabled) {
                    return;
                }

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
        x-cloak
        class="fixed inset-0 z-[200] flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
        aria-label="إعلان"
        data-ad-id="{{ $popupCampaign->id }}"
    >
        <div class="absolute inset-0 bg-black/50" aria-hidden="true"></div>

        <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden" @click.stop>
            @if($popupTargetUrl)
                <a href="{{ route('ads.click', $popupCampaign) }}"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="block">
                    <img src="{{ $popupCampaign->getFirstMediaUrl('ad_image', 'tablet') }}"
                         alt="{{ $popupCampaign->title }}"
                         class="w-full object-cover">
                </a>
            @else
                <div class="block">
                    <img src="{{ $popupCampaign->getFirstMediaUrl('ad_image', 'tablet') }}"
                         alt="{{ $popupCampaign->title }}"
                         class="w-full object-cover">
                </div>
            @endif

            <div class="flex items-center justify-between gap-3 px-4 py-3 border-t border-zinc-100 bg-zinc-50">
                <span class="text-xs text-zinc-500" x-show="!skipEnabled">
                    يمكنك التخطي بعد
                    <span class="font-bold text-zinc-700" x-text="countdown"></span>
                    ثوانٍ
                </span>
                <span class="text-xs text-zinc-400" x-show="skipEnabled" x-cloak></span>

                <button type="button"
                        @click="close()"
                        :disabled="!skipEnabled"
                        :class="skipEnabled
                            ? 'btn-nilex-primary cursor-pointer'
                            : 'bg-zinc-200 text-zinc-400 cursor-not-allowed'"
                        class="shrink-0 px-5 py-2 rounded-xl text-sm font-bold">
                    تخطي
                </button>
            </div>
        </div>
    </div>
@endif
