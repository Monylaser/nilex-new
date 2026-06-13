@props(['campaign'])

@if($campaign && $campaign->getFirstMediaUrl('ad_image'))
<div class="w-full my-4" data-ad-id="{{ $campaign->id }}">
    <a href="{{ route('ads.click', $campaign) }}"
       target="_blank"
       rel="noopener noreferrer"
       class="block w-full">
        <picture>
            <source media="(min-width: 1024px)"
                    srcset="{{ $campaign->getFirstMediaUrl('ad_image', 'desktop') }}">
            <source media="(min-width: 768px)"
                    srcset="{{ $campaign->getFirstMediaUrl('ad_image', 'tablet') }}">
            <img src="{{ $campaign->getFirstMediaUrl('ad_image', 'mobile') }}"
                 alt="{{ $campaign->title }}"
                 loading="lazy"
                 class="w-full rounded-xl object-cover shadow-sm">
        </picture>
    </a>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        fetch('/ads/{{ $campaign->id }}/impression', {
            method: 'GET',
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        }).catch(function() {});
    });
</script>
@endif
