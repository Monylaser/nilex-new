@php
    $isRtl = app()->getLocale() === 'ar';
    $localeLabel = fn ($row, $key) => $isRtl
        ? ($row['label_ar'] ?? $row['label'] ?? $key)
        : ($row['label_en'] ?? $row['label'] ?? $key);
@endphp
<x-app-layout>
    <div class="bg-zinc-50 min-h-screen pb-10" dir="{{ $isRtl ? 'rtl' : 'ltr' }}"
         x-data="{
            placement: @js(old('placement', 'hero_top')),
            duration: @js((int) old('duration_days', 7)),
            pricing: @js($pricingConfig),
            durations: @js($durations),
            currency: @js($currency),
            localeLabelKey: @js($isRtl ? 'label_ar' : 'label_en'),
            dayFallback: @js(__('ui.ads_dashboard.common.day_fallback')),
            get placementPrice() {
                const prices = this.pricing[this.placement]?.prices ?? {};
                return prices[this.duration] ?? 0;
            },
            get durationLabel() {
                return this.durations[this.duration]?.[this.localeLabelKey]
                    ?? this.dayFallback.replace(':count', this.duration);
            },
            get total() {
                return this.placementPrice;
            },
            formatAmount(value) {
                return new Intl.NumberFormat('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                }).format(value);
            }
         }">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-zinc-900">{{ __('ui.ads_dashboard.create.title') }}</h1>
                    <p class="text-sm text-zinc-500 mt-1">{{ __('ui.ads_dashboard.create.subtitle') }}</p>
                </div>
                <a href="{{ route('dashboard.ads.index') }}"
                   class="inline-flex items-center gap-2 text-sm font-bold text-nilex hover:text-nilex-dark">
                    ← {{ __('ui.ads_dashboard.common.back_campaigns') }}
                </a>
            </div>

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm font-semibold space-y-1">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('dashboard.ads.store') }}"
                  method="POST"
                  enctype="multipart/form-data"
                  class="space-y-6">
                @csrf

                <div class="bg-white rounded-2xl border border-zinc-100 p-6 space-y-5" style="box-shadow:0 1px 6px rgba(0,0,0,0.05);">

                    <div>
                        <label for="title" class="block text-sm font-bold text-zinc-700 mb-2">{{ __('ui.ads_dashboard.create.label_title') }}</label>
                        <input type="text"
                               id="title"
                               name="title"
                               value="{{ old('title') }}"
                               required
                               class="w-full rounded-xl border border-zinc-200 px-4 py-3 text-sm focus:ring-2 focus:ring-nilex/30 focus:border-nilex outline-none">
                    </div>

                    <div>
                        <label for="placement" class="block text-sm font-bold text-zinc-700 mb-2">{{ __('ui.ads_dashboard.create.label_placement') }}</label>
                        <select id="placement"
                                name="placement"
                                x-model="placement"
                                required
                                class="w-full rounded-xl border border-zinc-200 px-4 py-3 text-sm focus:ring-2 focus:ring-nilex/30 focus:border-nilex outline-none">
                            @foreach ($placements as $key => $placement)
                                <option value="{{ $key }}" @selected(old('placement') === $key)>
                                    {{ $localeLabel($placement, $key) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div x-show="placement === 'category_page'" x-cloak>
                        <label for="category_id" class="block text-sm font-bold text-zinc-700 mb-2">{{ __('ui.ads_dashboard.create.label_category') }}</label>
                        <select id="category_id"
                                name="category_id"
                                class="w-full rounded-xl border border-zinc-200 px-4 py-3 text-sm focus:ring-2 focus:ring-nilex/30 focus:border-nilex outline-none">
                            <option value="">{{ __('ui.ads_dashboard.create.select_category') }}</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((int) old('category_id') === $category->id)>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="target_url" class="block text-sm font-bold text-zinc-700 mb-2">{{ __('ui.ads_dashboard.create.label_target') }}</label>
                        <input type="url"
                               id="target_url"
                               name="target_url"
                               value="{{ old('target_url') }}"
                               required
                               placeholder="https://"
                               dir="ltr"
                               class="w-full rounded-xl border border-zinc-200 px-4 py-3 text-sm focus:ring-2 focus:ring-nilex/30 focus:border-nilex outline-none text-left">
                    </div>

                    <div>
                        <label for="ad_image" class="block text-sm font-bold text-zinc-700 mb-2">{{ __('ui.ads_dashboard.create.label_image') }}</label>
                        <input type="file"
                               id="ad_image"
                               name="ad_image"
                               accept="image/jpeg,image/png,image/webp,image/gif"
                               required
                               class="w-full rounded-xl border border-zinc-200 px-4 py-3 text-sm file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-nilex/10 file:text-nilex file:font-bold">
                        <p class="text-xs text-zinc-500 mt-2">{{ __('ui.ads_dashboard.create.image_hint') }}</p>
                    </div>

                    <div>
                        <label for="duration_days" class="block text-sm font-bold text-zinc-700 mb-2">{{ __('ui.ads_dashboard.create.label_duration') }}</label>
                        <select id="duration_days"
                                name="duration_days"
                                x-model.number="duration"
                                required
                                class="w-full rounded-xl border border-zinc-200 px-4 py-3 text-sm focus:ring-2 focus:ring-nilex/30 focus:border-nilex outline-none">
                            @foreach ($durations as $days => $duration)
                                <option value="{{ $days }}" @selected((int) old('duration_days', 7) === (int) $days)>
                                    {{ $localeLabel($duration, __('ui.ads_dashboard.common.day_fallback', ['count' => $days])) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="bg-zinc-50 border border-zinc-200 rounded-2xl p-5 space-y-3">
                    <h3 class="text-sm font-black text-zinc-800">{{ __('ui.ads_dashboard.create.calc_heading') }}</h3>

                    <div class="flex items-center justify-between text-sm">
                        <span class="text-zinc-500">{{ __('ui.ads_dashboard.create.calc_placement') }}</span>
                        <span class="font-bold text-zinc-800">
                            <span x-text="formatAmount(placementPrice)"></span>
                            <span class="text-xs text-zinc-500">{{ $currency }}</span>
                        </span>
                    </div>

                    <div class="flex items-center justify-between text-sm">
                        <span class="text-zinc-500">{{ __('ui.ads_dashboard.create.calc_duration') }}</span>
                        <span class="font-bold text-zinc-800" x-text="durationLabel"></span>
                    </div>

                    <div class="border-t border-zinc-200 pt-3 flex items-center justify-between">
                        <span class="text-sm font-black text-zinc-800">{{ __('ui.ads_dashboard.create.calc_total') }}</span>
                        <span class="text-lg font-black text-nilex">
                            <span x-text="formatAmount(total)"></span>
                            <span class="text-xs text-zinc-500">{{ $currency }}</span>
                        </span>
                    </div>
                </div>

                <button type="submit"
                        class="btn-nilex-primary w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 rounded-xl text-sm font-black">
                    {{ __('ui.ads_dashboard.create.submit') }}
                </button>
            </form>
        </div>
    </div>
</x-app-layout>
