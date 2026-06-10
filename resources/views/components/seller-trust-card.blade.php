{{--
    <x-seller-trust-card :seller="$listing->user" />

    Displays public seller trust indicators.
    Props:
      - seller : App\Models\User  (the listing owner)
--}}
@props(['seller'])

<div class="bg-white rounded-2xl border border-zinc-200 p-4 transition-colors hover:border-zinc-300">

    {{-- Seller identity --}}
    <div class="flex items-center gap-2.5 mb-3.5">
        <div class="w-10 h-10 bg-[#1D9E75]/10 rounded-xl flex items-center justify-center font-bold text-[#1D9E75] text-sm shrink-0 border border-[#1D9E75]/10">
            {{ mb_substr($seller->name, 0, 1) }}
        </div>
        <div class="flex-1 min-w-0">
            <p class="text-[11px] text-zinc-400 font-medium mb-0.5">البائع</p>
            <p class="text-sm font-semibold text-zinc-900 leading-snug truncate">{{ $seller->name }}</p>
        </div>
    </div>

    {{-- Trust badges --}}
    <div class="flex flex-col gap-2">

        {{-- Phone verified --}}
        @if($seller->is_phone_verified ?? false)
            <div class="flex items-center gap-2 text-xs">
                <div class="w-6 h-6 bg-[#1D9E75]/10 rounded-lg flex items-center justify-center shrink-0">
                    <svg class="w-3 h-3 text-[#1D9E75]" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <span class="font-medium text-zinc-700">رقم الهاتف موثق</span>
            </div>
        @else
            <div class="flex items-center gap-2 text-xs">
                <div class="w-6 h-6 bg-zinc-50 rounded-lg flex items-center justify-center shrink-0 border border-zinc-100">
                    <svg class="w-3 h-3 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                </div>
                <span class="text-zinc-400">رقم الهاتف غير موثق</span>
            </div>
        @endif

        {{-- Member since --}}
        <div class="flex items-center gap-2 text-xs">
            <div class="w-6 h-6 bg-zinc-50 rounded-lg flex items-center justify-center shrink-0 border border-zinc-100">
                <svg class="w-3 h-3 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <span class="text-zinc-500">عضو منذ {{ $seller->created_at->diffForHumans() }}</span>
        </div>

        {{-- Active listings --}}
        @php
            $listingCount = \App\Models\Listing::where('user_id', $seller->id)
                ->where('status', 'published')
                ->count();
        @endphp
        <div class="flex items-center gap-2 text-xs">
            <div class="w-6 h-6 bg-zinc-50 rounded-lg flex items-center justify-center shrink-0 border border-zinc-100">
                <svg class="w-3 h-3 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
            <span class="text-zinc-500">{{ $listingCount }} إعلان نشط</span>
        </div>

        {{-- Rating placeholder --}}
        <div class="flex items-center gap-2 pt-2.5 mt-0.5 border-t border-zinc-100">
            <div class="flex items-center gap-0.5">
                @for($i = 1; $i <= 5; $i++)
                    <svg class="w-3 h-3 text-zinc-200" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                @endfor
            </div>
            <span class="text-[11px] text-zinc-400 font-medium">لا توجد تقييمات بعد</span>
        </div>

    </div>

</div>
