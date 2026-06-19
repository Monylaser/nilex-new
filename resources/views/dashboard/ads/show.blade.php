<x-app-layout>
    <div class="bg-zinc-50 min-h-screen pb-10" dir="rtl">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-zinc-900">{{ $campaign->title }}</h1>
                    <p class="text-sm text-zinc-500 mt-1">تفاصيل الحملة الإعلانية</p>
                </div>
                <a href="{{ route('dashboard.ads.index') }}"
                   class="inline-flex items-center gap-2 text-sm font-bold text-nilex hover:text-nilex-dark">
                    ← العودة للحملات
                </a>
            </div>

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm font-semibold">
                    {{ $errors->first() }}
                </div>
            @endif

            @php
                $placementLabel = config("ad_pricing.placements.{$campaign->placement}.label_ar", $campaign->placement);
                $durationLabel = config("ad_pricing.durations.{$campaign->duration_days}.label_ar", $campaign->duration_days ? $campaign->duration_days . ' يوم' : '—');
                $amount = $campaign->amount_paid ?? $expectedAmount;
                $paymentLabels = [
                    'pending'  => ['label' => 'قيد الدفع', 'class' => 'bg-amber-50 text-amber-700'],
                    'paid'     => ['label' => 'مدفوع', 'class' => 'bg-green-50 text-green-700'],
                    'failed'   => ['label' => 'فشل', 'class' => 'bg-red-50 text-red-700'],
                    'refunded' => ['label' => 'مسترد', 'class' => 'bg-zinc-100 text-zinc-700'],
                ];
                $approvalLabels = [
                    'pending'  => ['label' => 'قيد الموافقة', 'class' => 'bg-amber-50 text-amber-700'],
                    'approved' => ['label' => 'موافق عليه', 'class' => 'bg-green-50 text-green-700'],
                    'rejected' => ['label' => 'مرفوض', 'class' => 'bg-red-50 text-red-700'],
                ];
                $payment = $paymentLabels[$campaign->payment_status] ?? ['label' => '—', 'class' => 'bg-zinc-100 text-zinc-700'];
                $approval = $approvalLabels[$campaign->approval_status] ?? ['label' => '—', 'class' => 'bg-zinc-100 text-zinc-700'];
                $banner = $campaign->getFirstMedia('ad_image');
            @endphp

            <div class="bg-white rounded-2xl border border-zinc-100 overflow-hidden" style="box-shadow:0 1px 6px rgba(0,0,0,0.05);">
                @if ($banner)
                    <div class="border-b border-zinc-100 bg-zinc-50 p-4">
                        <img src="{{ $banner->getUrl() }}"
                             alt="{{ $campaign->title }}"
                             class="w-full max-h-48 object-contain rounded-xl">
                    </div>
                @endif

                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-zinc-500 font-bold mb-1">الموضع</p>
                            <p class="font-semibold text-zinc-800">{{ $placementLabel }}</p>
                        </div>
                        <div>
                            <p class="text-zinc-500 font-bold mb-1">المدة</p>
                            <p class="font-semibold text-zinc-800">{{ $durationLabel }}</p>
                        </div>
                        <div>
                            <p class="text-zinc-500 font-bold mb-1">حالة الدفع</p>
                            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-bold {{ $payment['class'] }}">
                                {{ $payment['label'] }}
                            </span>
                        </div>
                        <div>
                            <p class="text-zinc-500 font-bold mb-1">حالة الموافقة</p>
                            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-bold {{ $approval['class'] }}">
                                {{ $approval['label'] }}
                            </span>
                        </div>
                        <div>
                            <p class="text-zinc-500 font-bold mb-1">المبلغ</p>
                            <p class="font-semibold text-zinc-800">
                                @if ($amount !== null)
                                    {{ number_format((float) $amount, 2) }} {{ $currency }}
                                @else
                                    —
                                @endif
                            </p>
                        </div>
                        <div>
                            <p class="text-zinc-500 font-bold mb-1">رابط الهدف</p>
                            <a href="{{ $campaign->target_url }}"
                               target="_blank"
                               rel="noopener"
                               dir="ltr"
                               class="font-semibold text-nilex hover:underline break-all">
                                {{ $campaign->target_url }}
                            </a>
                        </div>
                        @if ($campaign->category)
                            <div>
                                <p class="text-zinc-500 font-bold mb-1">التصنيف</p>
                                <p class="font-semibold text-zinc-800">{{ $campaign->category->name_ar }}</p>
                            </div>
                        @endif
                        <div>
                            <p class="text-zinc-500 font-bold mb-1">يبدأ</p>
                            <p class="font-semibold text-zinc-800">{{ $campaign->starts_at?->format('Y/m/d H:i') ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-zinc-500 font-bold mb-1">ينتهي</p>
                            <p class="font-semibold text-zinc-800">{{ $campaign->ends_at?->format('Y/m/d H:i') ?? '—' }}</p>
                        </div>
                    </div>

                    @if ($campaign->approval_status === 'rejected' && $campaign->rejected_reason)
                        <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-sm text-red-700">
                            <p class="font-bold mb-1">سبب الرفض</p>
                            <p>{{ $campaign->rejected_reason }}</p>
                        </div>
                    @endif

                    @if (in_array($campaign->payment_status, ['failed', 'pending'], true))
                        <form action="{{ route('dashboard.ads.retry-payment', $campaign) }}" method="POST">
                            @csrf
                            <button type="submit"
                                    class="inline-flex items-center px-5 py-2.5 rounded-xl bg-red-600 text-white text-sm font-bold hover:bg-red-700 transition">
                                إعادة محاولة الدفع
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
