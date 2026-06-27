{{--
    <x-rating-stars :avg="$user->ratings_avg" :count="$user->ratings_count" />

    Reusable rating display (partial-fill stars + translated summary), driven by
    the denormalized users.ratings_avg / ratings_count — no query. Shared by the
    public seller-trust-card and the seller dashboard so the star logic lives in
    one place. Renders the "no ratings yet" placeholder when count = 0.

    Props:
      - avg   : float|null  average rating (0–5)
      - count : int         number of reviews
--}}
@props(['avg' => 0, 'count' => 0])

@php
    $ratingsCount = (int) ($count ?? 0);
    $ratingsAvg   = (float) ($avg ?? 0);
    // Latin digits + drop trailing ".0" (e.g. 5.0 → "5", 4.5 → "4.5").
    $ratingsAvgDisplay = rtrim(rtrim(number_format($ratingsAvg, 1), '0'), '.');
@endphp

@if($ratingsCount > 0)
    <span class="inline-flex items-center gap-2">
        <span class="flex items-center gap-0.5">
            @for($i = 1; $i <= 5; $i++)
                @php($fill = max(0, min(1, $ratingsAvg - ($i - 1))) * 100)
                <span class="relative inline-block w-3 h-3 shrink-0">
                    <svg class="absolute inset-0 w-3 h-3 text-zinc-200" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                    <span class="absolute inset-0 overflow-hidden" style="width: {{ $fill }}%">
                        <svg class="w-3 h-3 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                    </span>
                </span>
            @endfor
        </span>
        <span class="text-[11px] text-zinc-600 font-medium">{{ __('ui.seller_trust.rating_summary', ['avg' => $ratingsAvgDisplay, 'count' => number_format($ratingsCount)]) }}</span>
    </span>
@else
    <span class="inline-flex items-center gap-2">
        <span class="flex items-center gap-0.5">
            @for($i = 1; $i <= 5; $i++)
                <svg class="w-3 h-3 text-zinc-200" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                </svg>
            @endfor
        </span>
        <span class="text-[11px] text-zinc-400 font-medium">{{ __('ui.seller_trust.no_ratings') }}</span>
    </span>
@endif
