@props([
    'placement',
    'categoryId' => null,
    'wrapperClass' => null,
])

@php
    $campaign = app(\App\Services\AdCampaignService::class)
        ->getForPlacement($placement, $categoryId);
    $imageUrl = $campaign?->getFirstMediaUrl('ad_image');
    $targetUrl = $campaign?->target_url;
    $isHero = $placement === 'hero_top';
    $imgClass = $isHero
        ? 'w-full aspect-[3/1] md:aspect-[5/1] max-h-[160px] md:max-h-[200px] object-cover'
        : 'w-full rounded-xl object-cover shadow-sm';
    $imgLoading = $isHero ? 'eager' : 'lazy';
@endphp

@if($campaign && $imageUrl)
        @if($wrapperClass)
            <div {{ $attributes->merge(['class' => $wrapperClass]) }}>
        @endif

        @if($targetUrl)
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
                         loading="{{ $imgLoading }}"
                         class="{{ $imgClass }}">
                </picture>
            </a>
        @else
            <div class="block w-full"
                 data-ad-id="{{ $campaign->id }}"
                 data-ad-impression="{{ route('ads.impression', $campaign) }}">
                <picture>
                    <source media="(min-width: 1024px)"
                            srcset="{{ $campaign->getFirstMediaUrl('ad_image', 'desktop') }}">
                    <source media="(min-width: 768px)"
                            srcset="{{ $campaign->getFirstMediaUrl('ad_image', 'tablet') }}">
                    <img src="{{ $campaign->getFirstMediaUrl('ad_image', 'mobile') }}"
                         alt="{{ $campaign->title }}"
                         loading="{{ $imgLoading }}"
                         class="{{ $imgClass }}">
                </picture>
            </div>
        @endif

        @if($wrapperClass)
            </div>
        @endif

    <x-ad-impression-tracker />
@endif
