<div class="py-12 bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col lg:flex-row gap-8">
            
            {{-- ── العمود الجانبي (الفلاتر) ───────────────────────────────── --}}
            <div class="w-full lg:w-1/4">
                <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 sticky top-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-black text-gray-900">{{ __('ui.listing_grid.filter_title') }} 🔍</h3>
                        <div wire:loading class="text-sm font-bold text-blue-600 animate-pulse">
                            {{ __('ui.listing_grid.updating') }}
                        </div>
                    </div>

                    {{-- 📍 زرار تحديد الموقع الجغرافي (GPS) --}}
                    <div class="mb-6">
                        @if($lat && $lng)
                            <div class="bg-emerald-50 border border-emerald-200 p-4 rounded-xl text-center">
                                <p class="text-sm font-bold text-emerald-700 mb-2">📍 {{ __('ui.listing_grid.location_set') }}</p>
                                <button wire:click="clearLocation" class="text-xs text-red-600 hover:underline font-bold">
                                    {{ __('ui.listing_grid.clear_location') }} ❌
                                </button>
                            </div>
                        @else
                            <button onclick="requestUserLocation()" class="w-full flex items-center justify-center gap-2 bg-gray-900 hover:bg-gray-800 text-white py-3 rounded-xl font-bold transition-all shadow-sm">
                                <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                                {{ __('ui.listing_grid.near_me') }}
                            </button>
                            <p class="text-[10px] text-gray-400 text-center mt-2">{{ __('ui.listing_grid.location_hint') }}</p>
                        @endif
                    </div>

                    <hr class="border-gray-100 mb-6">

                    {{-- البحث بالكلمة --}}
                    <div class="mb-5">
                        <label class="block text-sm font-bold text-gray-700 mb-2">{{ __('ui.listing_grid.search_label') }}</label>
                        <input type="text" wire:model.live.debounce.500ms="search" placeholder="{{ __('ui.listing_grid.search_placeholder') }}" 
                            class="w-full rounded-xl border-gray-200 focus:ring-blue-500 focus:border-blue-500 shadow-sm text-sm">
                    </div>

                    {{-- القسم --}}
                    <div class="mb-5">
                        <label class="block text-sm font-bold text-gray-700 mb-2">{{ __('ui.listing_grid.category_label') }}</label>
                        <select wire:model.live="category_id" class="w-full rounded-xl border-gray-200 focus:ring-blue-500 focus:border-blue-500 shadow-sm text-sm">
                            <option value="">{{ __('ui.listing_grid.all_categories') }}</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- الماركة (تظهر فقط لو اختار سيارات) --}}
                    @if($category_id == 2) 
                    <div class="mb-5 bg-blue-50 p-4 rounded-xl border border-blue-100">
                        <label class="block text-sm font-bold text-blue-900 mb-2">{{ __('ui.listing_grid.car_brand_label') }}</label>
                        <select wire:model.live="car_brand_id" class="w-full rounded-lg border-blue-200 focus:ring-blue-500 focus:border-blue-500 text-sm">
                            <option value="">{{ __('ui.listing_grid.all_brands') }}</option>
                            @foreach($carBrands as $brand)
                                <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    {{-- السعر --}}
                    <div class="mb-5">
                        <label class="block text-sm font-bold text-gray-700 mb-2">{{ __('ui.listing_grid.price_label') }}</label>
                        <div class="flex gap-2">
                            <input type="number" wire:model.live.debounce.500ms="min_price" placeholder="{{ __('ui.listing_grid.price_from') }}" class="w-1/2 rounded-xl border-gray-200 text-sm">
                            <input type="number" wire:model.live.debounce.500ms="max_price" placeholder="{{ __('ui.listing_grid.price_to') }}" class="w-1/2 rounded-xl border-gray-200 text-sm">
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── شبكة الإعلانات ────────────────────────────────────────── --}}
            <div class="w-full lg:w-3/4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @forelse($listings as $listing)
                        @include('frontend.partials.listing-card', ['listing' => $listing])
                    @empty
                        <div class="col-span-full flex flex-col items-center justify-center py-24 bg-white rounded-3xl border border-gray-100 text-center shadow-sm">
                            <div class="text-6xl mb-4 opacity-50">🧭</div>
                            <h3 class="text-2xl font-black text-gray-900 mb-2">{{ __('ui.listing_grid.empty_title') }}</h3>
                            <p class="text-gray-500 font-medium">{{ __('ui.listing_grid.empty_subtitle') }}</p>
                        </div>
                    @endforelse
                </div>

                <div class="mt-10">
                    {{ $listings->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

{{-- سكريبت الجافاسكريبت لجلب الـ GPS من المتصفح والتواصل مع Livewire --}}
<script>
    function requestUserLocation() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    // إرسال الإحداثيات للكنترولر في Livewire
                   $wire.setLocation(position.coords.latitude, position.coords.longitude);
                }, 
                function(error) {
                    alert("{{ __('ui.listing_grid.alert_denied') }}");
                },
                { enableHighAccuracy: true }
            );
        } else {
            alert("{{ __('ui.listing_grid.alert_unsupported') }}");
        }
    }
</script>
