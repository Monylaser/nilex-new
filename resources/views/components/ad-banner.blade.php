@props([
    'placement',
    'categoryId' => null,
    'wrapperClass' => null,
])

@if(config('features.self_service_ads'))
    @php
        $campaign = app(\App\Services\AdCampaignService::class)
            ->getForPlacement($placement, $categoryId);
        $imageUrl = $campaign?->getFirstMediaUrl('ad_image');
        $targetUrl = $campaign?->target_url;
    @endphp

    @if($campaign && $imageUrl && $targetUrl)
        @if($wrapperClass)
            <div {{ $attributes->merge(['class' => $wrapperClass]) }}>
        @endif

        <a href="{{ route('ads.click', $campaign) }}"
           target="_blank"
           rel="noopener noreferrer"
           class="block w-full"
           data-ad-id="{{ $campaign->id }}"
           data-ad-impression="{{ route('ads.impression', $campaign) }}">
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

        @if($wrapperClass)
            </div>
        @endif

        <x-ad-impression-tracker />
    @endif
@endif
