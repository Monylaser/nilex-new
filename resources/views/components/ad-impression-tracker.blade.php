@once
    @push('scripts')
        <script>
            (function () {
                var observed = new WeakSet();

                function trackImpression(url) {
                    fetch(url, {
                        method: 'GET',
                        headers: {'X-Requested-With': 'XMLHttpRequest'},
                    }).catch(function () {});
                }

                function observeBanner(element) {
                    if (observed.has(element)) {
                        return;
                    }

                    var url = element.getAttribute('data-ad-impression');

                    if (!url) {
                        return;
                    }

                    observed.add(element);

                    if (!('IntersectionObserver' in window)) {
                        return;
                    }

                    var observer = new IntersectionObserver(function (entries) {
                        entries.forEach(function (entry) {
                            if (!entry.isIntersecting) {
                                return;
                            }

                            trackImpression(url);
                            observer.unobserve(entry.target);
                        });
                    }, {threshold: 0.5});

                    observer.observe(element);
                }

                function scanBanners() {
                    document.querySelectorAll('[data-ad-impression]').forEach(observeBanner);
                }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', scanBanners);
                } else {
                    scanBanners();
                }
            })();
        </script>
    @endpush
@endonce
