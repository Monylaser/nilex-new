@props([
    'pricingConfig' => [],
    'durations' => [],
    'currency' => 'EGP',
    'initialPlacement' => 'hero_top',
    'initialDuration' => 7,
])

<div
    x-data="{
        placement: @js(old('placement', $initialPlacement)),
        duration: @js((int) old('duration_days', $initialDuration)),
        pricing: @js($pricingConfig),
        durations: @js($durations),
        currency: @js($currency),
        get placementPrice() {
            const prices = this.pricing[this.placement]?.prices ?? {};
            return prices[this.duration] ?? 0;
        },
        get durationLabel() {
            return this.durations[this.duration]?.label_ar ?? this.duration + ' يوم';
        },
        get total() {
            return this.placementPrice;
        },
        formatAmount(value) {
            return new Intl.NumberFormat('ar-EG', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(value);
        }
    }"
    class="bg-zinc-50 border border-zinc-200 rounded-2xl p-5 space-y-3"
>
    <h3 class="text-sm font-black text-zinc-800">حاسبة السعر</h3>

    <div class="flex items-center justify-between text-sm">
        <span class="text-zinc-500">سعر الموضع</span>
        <span class="font-bold text-zinc-800">
            <span x-text="formatAmount(placementPrice)"></span>
            <span class="text-xs text-zinc-500">{{ $currency }}</span>
        </span>
    </div>

    <div class="flex items-center justify-between text-sm">
        <span class="text-zinc-500">المدة</span>
        <span class="font-bold text-zinc-800" x-text="durationLabel"></span>
    </div>

    <div class="border-t border-zinc-200 pt-3 flex items-center justify-between">
        <span class="text-sm font-black text-zinc-800">الإجمالي</span>
        <span class="text-lg font-black text-nilex">
            <span x-text="formatAmount(total)"></span>
            <span class="text-xs text-zinc-500">{{ $currency }}</span>
        </span>
    </div>
</div>
