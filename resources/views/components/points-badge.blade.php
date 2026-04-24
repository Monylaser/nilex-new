@props(['points' => 0])

@php
    // تحديد البيانات بناءً على نقاط v0
    if ($points > 1000) {
        $name = "Legendary";
        $gradient = "from-amber-400 via-yellow-300 to-amber-500";
        $textColor = "text-amber-950";
        $icon = "diamond";
        $isLegendary = true;
    } elseif ($points > 500) {
        $name = "Elite";
        $gradient = "from-rose-400 to-pink-600";
        $textColor = "text-white";
        $icon = "diamond";
        $isLegendary = false;
    } elseif ($points > 100) {
        $name = "Active";
        $gradient = "from-indigo-500 to-purple-600";
        $textColor = "text-white";
        $icon = "star";
        $isLegendary = false;
    } else {
        $name = "Newbie";
        $gradient = "from-sky-400 to-blue-500";
        $textColor = "text-white";
        $icon = "star";
        $isLegendary = false;
    }
@endphp

<div class="relative inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold transition-all duration-300 bg-gradient-to-r {{ $gradient }} {{ $textColor }} {{ $isLegendary ? 'shadow-md shadow-amber-500/20' : '' }} border border-white/20">

    {{-- تأثير اللمعان (فقط للذهبي) --}}
    @if($isLegendary)
        <div class="absolute inset-0 rounded-full overflow-hidden pointer-events-none">
            <div class="absolute inset-0 animate-shine"
                 style="background-image: linear-gradient(90deg, transparent 0%, rgba(255,255,255,0.3) 50%, transparent 100%); background-size: 200% 100%;">
            </div>
        </div>
    @endif

    {{-- الأيقونة - تحديد العرض والارتفاع بدقة لمنع الصليب --}}
    <span class="relative z-10 flex items-center justify-center">
        @if($icon === 'diamond')
            <svg class="w-3 h-3 {{ $isLegendary ? 'animate-pulse' : '' }}" fill="currentColor" viewBox="0 0 24 24" style="width: 12px; height: 12px;">
                <path d="M12 2L2 9l10 13 10-13-10-7z" />
            </svg>
        @else
            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24" style="width: 12px; height: 12px;">
                <path d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.006 5.404.434c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.434 2.082-5.005Z" />
            </svg>
        @endif
    </span>

    {{-- النقاط --}}
    <span class="relative z-10 tabular-nums whitespace-nowrap">
        {{ number_format($points) }} pts
    </span>
</div>
