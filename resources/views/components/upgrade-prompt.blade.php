{{--
    <x-upgrade-prompt
        feature-name="مخططات التحليلات"
        required-tier="pro_seller"
        :current-tier="$user->plan_tier"
    />
--}}
@props([
    'featureName' => '',
    'requiredTier' => 'growth',
    'currentTier' => null,
])

@php
    $tierLabels = [
        'starter'    => __('ui.analytics.tiers.starter'),
        'growth'     => __('ui.analytics.tiers.growth'),
        'pro_seller' => __('ui.analytics.tiers.pro_seller'),
        'business'   => __('ui.analytics.tiers.business'),
    ];
    $requiredLabel = $tierLabels[$requiredTier] ?? $requiredTier;
@endphp

<div {{ $attributes->merge(['class' => 'relative rounded-2xl overflow-hidden border border-zinc-100']) }}>
    <div class="absolute inset-0 bg-white/60 backdrop-blur-sm z-10 flex flex-col items-center justify-center p-6 text-center">
        <span class="text-2xl mb-2" aria-hidden="true">🔒</span>
        <p class="text-sm font-bold text-zinc-700 mb-1">
            {{ __('ui.analytics.upgrade_locked', ['tier' => $requiredLabel]) }}
        </p>
        @if($featureName)
            <p class="text-xs text-zinc-500 mb-4">{{ $featureName }}</p>
        @endif
        <a href="{{ route('pricing') }}"
           class="btn-nilex-primary inline-flex items-center gap-2 py-2.5 px-5 rounded-xl text-sm">
            {{ __('ui.analytics.upgrade_cta') }}
        </a>
    </div>
    <div class="opacity-40 pointer-events-none select-none p-5 min-h-[12rem] bg-zinc-50">
        {{ $slot }}
    </div>
</div>
