{{-- Business tier badge — display only when seller has business_badge entitlement --}}
@props(['seller'])

@php
    $showBadge = $seller && app(\App\Services\EntitlementService::class)->hasFeature(
        $seller,
        \App\Services\EntitlementService::FEATURE_BUSINESS_BADGE,
    );
@endphp

@if($showBadge)
    <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200']) }}>
        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
            <path fill-rule="evenodd" d="M6 6V5a3 3 0 013-3h2a3 3 0 013 3v1h2a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2h2zm2-1a1 1 0 011-1h2a1 1 0 011 1v1H8V5z" clip-rule="evenodd"/>
        </svg>
        أعمال
    </span>
@endif
