@props(['points' => 0])

@php
    if ($points > 1000) {
        $label    = 'مميز';
        $bg       = 'background: linear-gradient(135deg, #085041, #1D9E75);';
        $textColor = 'color: white;';
        $icon     = '★';
    } elseif ($points > 500) {
        $label    = 'نشيط';
        $bg       = 'background: linear-gradient(135deg, #1D9E75, #085041);';
        $textColor = 'color: white;';
        $icon     = '●';
    } elseif ($points > 100) {
        $label    = 'عضو';
        $bg       = 'background: #f4f4f5; border: 1px solid #d4d4d8;';
        $textColor = 'color: #3f3f46;';
        $icon     = '●';
    } else {
        $label    = 'جديد';
        $bg       = 'background: #f4f4f5; border: 1px solid #d4d4d8;';
        $textColor = 'color: #71717a;';
        $icon     = '○';
    }
@endphp

<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold"
      style="{{ $bg }} {{ $textColor }}">
    <span class="text-[10px] leading-none">{{ $icon }}</span>
    <span class="tabular-nums">{{ number_format($points) }}</span>
    <span class="opacity-80">نقطة</span>
</span>
