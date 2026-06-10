{{--
    Cookie Consent Banner
    ─────────────────────
    • Vanilla JS + localStorage only (no Alpine dependency)
    • Pure Tailwind CSS — no external stylesheets
    • RTL / LTR aware via document.documentElement.dir
    • Keys: cookie_consent = 'accepted' | 'declined'
    • Linked to route('legal.show', 'cookies-policy')
--}}

@if(!request()->is('admin/*'))
<div
    id="cookie-consent-banner"
    aria-live="polite"
    role="region"
    aria-label="{{ trans('ui.cookies.message') }}"
    class="fixed bottom-0 left-0 right-0 z-50 translate-y-full opacity-0 transition-all duration-500 ease-out"
    style="display:none;"
>
    <div class="bg-[#0f172a] border-t-2 border-[#1D9E75] shadow-2xl">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4"
                 dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

                {{-- Text section --}}
                <div class="flex items-start gap-3 flex-1 min-w-0">
                    <span class="text-2xl leading-none mt-0.5 shrink-0" aria-hidden="true">🍪</span>
                    <p class="text-gray-300 text-sm leading-relaxed">
                        {{ trans('ui.cookies.message') }}
                        <a href="{{ route('legal.show', 'cookies-policy') }}"
                           class="text-[#1D9E75] underline hover:text-emerald-400 transition-colors ms-1 whitespace-nowrap">
                            {{ trans('ui.cookies.learn_more') }}
                        </a>
                    </p>
                </div>

                {{-- Buttons section --}}
                <div class="flex items-center gap-3 shrink-0">
                    <button
                        id="cookie-accept-btn"
                        type="button"
                        class="bg-[#1D9E75] hover:bg-[#085041] active:scale-95 text-white text-sm font-bold px-5 py-2.5 rounded-xl transition-all whitespace-nowrap focus:outline-none focus:ring-2 focus:ring-[#1D9E75] focus:ring-offset-2 focus:ring-offset-[#0f172a]">
                        {{ trans('ui.cookies.accept') }}
                    </button>
                    <button
                        id="cookie-decline-btn"
                        type="button"
                        class="border border-white/30 hover:border-white/70 text-white text-sm font-bold px-5 py-2.5 rounded-xl transition-all whitespace-nowrap focus:outline-none focus:ring-2 focus:ring-white/30 focus:ring-offset-2 focus:ring-offset-[#0f172a]">
                        {{ trans('ui.cookies.decline') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var banner = document.getElementById('cookie-consent-banner');
    if (!banner) return;

    if (localStorage.getItem('cookie_consent') !== null) return;

    // Show the banner with a slide-up animation
    banner.style.display = 'block';
    // Trigger reflow so the transition plays
    banner.getBoundingClientRect();
    banner.classList.remove('translate-y-full', 'opacity-0');

    function hideBanner() {
        banner.classList.add('translate-y-full', 'opacity-0');
        setTimeout(function () { banner.style.display = 'none'; }, 500);
    }

    document.getElementById('cookie-accept-btn').addEventListener('click', function () {
        localStorage.setItem('cookie_consent', 'accepted');
        hideBanner();
    });

    document.getElementById('cookie-decline-btn').addEventListener('click', function () {
        localStorage.setItem('cookie_consent', 'declined');
        hideBanner();
    });
}());
</script>
@endif
