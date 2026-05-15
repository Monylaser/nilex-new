<div class="py-12 bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {{-- ── الترحيب والإحصائيات العلوية ────────────────────────────────────────── --}}
        <div class="mb-8 flex flex-col md:flex-row justify-between items-center bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
            <div class="flex items-center gap-4 mb-4 md:mb-0">
                <div class="w-16 h-16 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-2xl font-black">
                    {{ mb_substr($user->name, 0, 1) }}
                </div>
                <div>
                    <h2 class="text-2xl font-black text-gray-900">أهلاً بك، {{ explode(' ', $user->name)[0] }}! 👋</h2>
                    <p class="text-gray-500 font-medium">رصيد نقاطك الحالي: <span class="text-yellow-500 font-bold text-lg">{{ $user->points ?? 0 }} 🪙</span></p>
                </div>
            </div>
            <a href="{{ route('listings.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-xl transition-colors shadow-sm">
                + أضف إعلان جديد
            </a>
        </div>

        {{-- ── كروت الإحصائيات الشاملة والتحليلات ─────────────────────────────── --}}
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 text-center">
                <div class="text-3xl mb-2">📦</div>
                <div class="text-gray-500 text-sm font-bold">كل الإعلانات</div>
                <div class="text-2xl font-black text-gray-900">{{ $stats['total'] }}</div>
            </div>
            <div class="bg-emerald-50 p-6 rounded-2xl shadow-sm border border-emerald-100 text-center">
                <div class="text-3xl mb-2">✅</div>
                <div class="text-emerald-600 text-sm font-bold">نشط ومنشور</div>
                <div class="text-2xl font-black text-emerald-700">{{ $stats['active'] }}</div>
            </div>
            <div class="bg-amber-50 p-6 rounded-2xl shadow-sm border border-amber-100 text-center">
                <div class="text-3xl mb-2">⏳</div>
                <div class="text-amber-600 text-sm font-bold">قيد المراجعة</div>
                <div class="text-2xl font-black text-amber-700">{{ $stats['pending'] }}</div>
            </div>
            <div class="bg-red-50 p-6 rounded-2xl shadow-sm border border-red-100 text-center">
                <div class="text-3xl mb-2">⛔</div>
                <div class="text-red-600 text-sm font-bold">مرفوض</div>
                <div class="text-2xl font-black text-red-700">{{ $stats['rejected'] }}</div>
            </div>
            <div class="bg-indigo-50 p-6 rounded-2xl shadow-sm border border-indigo-100 text-center">
                <div class="text-3xl mb-2">👁️</div>
                <div class="text-indigo-600 text-sm font-bold">إجمالي المشاهدات</div>
                <div class="text-2xl font-black text-indigo-700">{{ number_format($stats['views']) }}</div>
            </div>
            <div class="bg-[#25D366]/10 p-6 rounded-2xl shadow-sm border border-[#25D366]/20 text-center">
                <div class="text-3xl mb-2">💬</div>
                <div class="text-[#25D366] text-sm font-bold">نقرات الواتساب</div>
                <div class="text-2xl font-black text-[#1da851]">{{ number_format($stats['clicks']) }}</div>
            </div>
        </div>

        {{-- 📊 الرسم البياني --}}
        <div class="mb-8 bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
            <h3 class="text-xl font-black text-gray-900 mb-6 flex items-center gap-2">
                <span>أداء الإعلانات الأخيرة</span>
                <span class="text-xs font-medium text-gray-400">(آخر 7 إعلانات)</span>
            </h3>
            
            {{-- 💡 تخزين البيانات في Data Attributes لمنع أخطاء الـ Editor --}}
            <div id="chartDataContainer" 
                 data-labels="{{ json_encode(collect($listings->items())->take(7)->pluck('title')->map(fn($t) => mb_substr($t, 0, 15) . '...')->reverse()->values()) }}"
                 data-views="{{ json_encode(collect($listings->items())->take(7)->pluck('views_count')->reverse()->values()) }}"
                 data-clicks="{{ json_encode(collect($listings->items())->take(7)->pluck('whatsapp_clicks')->reverse()->values()) }}"
                 class="relative h-[300px] w-full">
                <canvas id="userAnalyticsChart"></canvas>
            </div>
        </div>
        {{-- ── قسم العروض المستلمة الجديد 🤝 ────────────────────────────────── --}}
        @if($incomingOffers->count() > 0)
        <div class="mb-8">
            <h3 class="text-xl font-black text-gray-900 mb-4 flex items-center gap-2">
                <span>عروض سعر جديدة</span>
                <span class="bg-indigo-600 text-white text-xs px-2 py-1 rounded-full">{{ $incomingOffers->count() }}</span>
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($incomingOffers as $offer)
                <div class="bg-white p-5 rounded-3xl shadow-sm border-2 border-indigo-50 flex flex-col justify-between">
                    <div>
                        <div class="flex justify-between items-start mb-3">
                            <span class="text-xs font-bold text-indigo-600 bg-indigo-50 px-2 py-1 rounded-lg">عرض سعر</span>
                            <span class="text-sm font-black text-gray-900">{{ number_format($offer->amount) }} ج.م</span>
                        </div>
                        <h4 class="font-bold text-gray-800 text-sm mb-1 line-clamp-1">على: {{ $offer->listing->title }}</h4>
                        <p class="text-xs text-gray-500 mb-3">من: {{ $offer->sender->name }}</p>
                        
                        @if($offer->message)
                        <div class="bg-gray-50 p-3 rounded-xl text-xs text-gray-600 italic mb-4">
                            "{{ $offer->message }}"
                        </div>
                        @endif
                    </div>

                    <div class="flex gap-2">
                        <button wire:click="acceptOffer({{ $offer->id }})" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold py-2 rounded-lg transition">
                            قبول
                        </button>
                        <button wire:click="rejectOffer({{ $offer->id }})" class="flex-1 bg-white border border-red-200 text-red-600 hover:bg-red-50 text-xs font-bold py-2 rounded-lg transition">
                            رفض
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- ── رسائل التنبيه ────────────────────────────────────────────────── --}}
        @if (session()->has('success'))
            <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl font-bold">
                {{ session('success') }}
            </div>
        @endif
        @if (session()->has('error'))
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl font-bold">
                {{ session('error') }}
            </div>
        @endif

        {{-- ── قائمة الإعلانات ────────────────────────────────────────────────── --}}
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-100">
                <h3 class="text-xl font-black text-gray-900">إعلاناتي 📋</h3>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-right">
                    <thead class="bg-gray-50 text-gray-500 text-sm font-bold">
                        <tr>
                            <th class="px-6 py-4">الإعلان</th>
                            <th class="px-6 py-4">القسم</th>
                            <th class="px-6 py-4">السعر</th>
                            <th class="px-6 py-4">الحالة</th>
                            <th class="px-6 py-4 text-center">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($listings as $listing)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-12 h-12 rounded-lg bg-gray-200 overflow-hidden shrink-0">
                                            @if($listing->getFirstMediaUrl('images', 'thumb'))
                                                <img src="{{ $listing->getFirstMediaUrl('images', 'thumb') }}" class="w-full h-full object-cover">
                                            @else
                                                <div class="w-full h-full flex items-center justify-center text-gray-400 text-xs">بدون صورة</div>
                                            @endif
                                        </div>
                                        <div>
                                            <a href="{{ route('listings.show', $listing) }}" class="font-bold text-gray-900 hover:text-blue-600 transition-colors block max-w-xs truncate">
                                                {{ $listing->title }}
                                            </a>
                                            <div class="text-xs text-gray-500 mt-1">{{ $listing->created_at->diffForHumans() }}</div>
                                            <div class="flex gap-2 mt-2">
                                                <span class="inline-flex items-center gap-1 bg-gray-100 px-2 py-1 rounded-md text-[10px] font-bold text-gray-600">
                                                    👁️ {{ $listing->views_count ?? 0 }}
                                                </span>
                                                <span class="inline-flex items-center gap-1 bg-[#25D366]/10 px-2 py-1 rounded-md text-[10px] font-bold text-[#25D366]">
                                                    💬 {{ $listing->whatsapp_clicks ?? 0 }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-800">
                                        {{ $listing->category->name_ar ?? 'بدون قسم' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-bold text-gray-900">
                                    {{ number_format($listing->price) }} ج.م
                                </td>
                                <td class="px-6 py-4">
                                    @if($listing->status === 'published')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">نشط</span>
                                    @elseif($listing->status === 'pending')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800">مراجعة</span>
                                    @elseif($listing->status === 'rejected')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-800" title="{{ $listing->rejection_reason }}">مرفوض ⚠️</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex justify-center items-center gap-3">
                                        @if(!$listing->is_featured && $listing->status === 'published')
                                            <button wire:click="featureListing({{ $listing->id }})" wire:confirm="هل تريد خصم 50 نقطة لتمييز الإعلان؟" class="text-gray-400 hover:text-yellow-500 transition-colors">
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" /></svg>
                                            </button>
                                        @elseif($listing->is_featured)
                                            <span class="text-yellow-500" title="إعلان مميز">⭐</span>
                                        @endif
                                        <button wire:click="deleteListing({{ $listing->id }})" wire:confirm="هل أنت متأكد من الحذف؟" class="text-red-400 hover:text-red-600 transition-colors">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500 font-bold">لا يوجد إعلانات حالياً.. ابدأ بنشر أول إعلان لك الآن! 🚀</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-6 border-t border-gray-100">
                {{ $listings->links() }}
            </div>
        </div>
    </div>
</div>

@section('footer-scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // استخدام دالة بسيطة لضمان تحميل الرسم البياني مع Livewire
    function initMyChart() {
        const container = document.getElementById('chartDataContainer');
        const canvas = document.getElementById('userAnalyticsChart');
        
        if (container && canvas) {
            // سحب البيانات من الـ Attributes (طريقة نظيفة 100% لا تسبب أخطاء)
            const labels = JSON.parse(container.getAttribute('data-labels'));
            const views = JSON.parse(container.getAttribute('data-views'));
            const clicks = JSON.parse(container.getAttribute('data-clicks'));

            new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'المشاهدات',
                            data: views,
                            backgroundColor: 'rgba(37, 99, 235, 0.5)',
                            borderColor: '#2563eb',
                            borderWidth: 2,
                            borderRadius: 8,
                        },
                        {
                            label: 'نقرات الواتساب',
                            data: clicks,
                            backgroundColor: 'rgba(37, 211, 102, 0.5)',
                            borderColor: '#1da851',
                            borderWidth: 2,
                            borderRadius: 8,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    rtl: true,
                    scales: {
                        y: { beginAtZero: true, grid: { display: false } },
                        x: { grid: { display: false } }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            align: 'end',
                            labels: { font: { family: 'Cairo', size: 12, weight: 'bold' } }
                        }
                    }
                }
            });
        }
    }

    // التشغيل عند أول تحميل وعند التنقل في Livewire
    document.addEventListener('DOMContentLoaded', initMyChart);
    document.addEventListener('livewire:navigated', initMyChart);
</script>
@endsection