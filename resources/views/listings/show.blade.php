{{-- resources/views/listings/show.blade.php --}}

@push('meta')
    {{-- 🚀 تحسينات الـ SEO ووسائل التواصل الاجتماعي --}}
    <meta name="description" content="{{ Str::limit(strip_tags($listing->description), 160) }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $listing->title }} - {{ number_format($listing->price) }} ج.م">
    <meta property="og:description" content="شاهد هذا الإعلان على منصة نايلكس: {{ Str::limit(strip_tags($listing->description), 100) }}">
    <meta property="og:image" content="{{ $listing->getFirstMediaUrl('listings', 'full_hd') }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $listing->title }}">
    <meta name="twitter:image" content="{{ $listing->getFirstMediaUrl('listings', 'full_hd') }}">
@endpush

<x-app-layout>
    <div class="py-12 bg-gray-50">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-3xl shadow-sm overflow-hidden p-8">
                
                {{-- ── 1. معرض الصور (Spatie Media) ────────────────────────────────── --}}
                @php
                    $media = $listing->getMedia('listings');
                @endphp
                
                @if($media->isNotEmpty())
                    <div class="mb-8">
                        {{-- الصورة الرئيسية --}}
                        <div class="w-full h-[400px] bg-gray-100 rounded-2xl overflow-hidden mb-4 border border-gray-100 shadow-inner">
                            <img src="{{ $media[0]->getUrl('full_hd') }}" alt="{{ $listing->title }}" class="w-full h-full object-cover">
                        </div>
                        
                        {{-- الصور المصغرة --}}
                        @if($media->count() > 1)
                            <div class="flex gap-3 overflow-x-auto pb-2 custom-scrollbar">
                                @foreach($media->skip(1) as $image)
                                    <div class="w-24 h-24 shrink-0 rounded-xl overflow-hidden border-2 border-transparent hover:border-blue-500 transition-colors cursor-pointer">
                                        <img src="{{ $image->getUrl('thumb') }}" alt="" class="w-full h-full object-cover">
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif

                {{-- ── 2. العنوان والسعر ───────────────────────────────────────────── --}}
                <div class="flex flex-col md:flex-row justify-between items-start mb-6 gap-4">
                    <div>
                        <h1 class="text-3xl font-black text-gray-900">{{ $listing->title }}</h1>
                        <p class="text-gray-500 mt-2 font-medium">
                            <svg class="w-4 h-4 inline text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            {{ $listing->province->name_ar ?? '' }} - {{ $listing->location->name_ar ?? '' }}
                        </p>
                        <p class="text-gray-500 mt-2 font-bold text-sm bg-gray-100 inline-block px-3 py-1 rounded-lg">
                            👁️ تم مشاهدته {{ $listing->views_count }} مرة
                        </p>
                    </div>
                    <div class="text-left bg-blue-50 px-6 py-3 rounded-2xl border border-blue-100">
                        <span class="text-2xl font-black text-blue-600">{{ number_format($listing->price) }} ج.م</span>
                    </div>
                </div>

                <hr class="my-8 border-gray-100">

                {{-- ── 3. الحقول المخصصة ───────────────────────────────────────────── --}}
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-10">
                    @if($listing->carBrand)
                        <div class="bg-gray-50 p-4 rounded-2xl text-center border border-gray-100">
                            <p class="text-xs text-gray-500 font-bold mb-1">الماركة</p>
                            <p class="font-black text-gray-900">{{ $listing->carBrand->name_ar }}</p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-2xl text-center border border-gray-100">
                            <p class="text-xs text-gray-500 font-bold mb-1">الموديل</p>
                            <p class="font-black text-gray-900">{{ $listing->carModel->name_ar }}</p>
                        </div>
                        @if(isset($listing->custom_fields_values['year']))
                        <div class="bg-gray-50 p-4 rounded-2xl text-center border border-gray-100">
                            <p class="text-xs text-gray-500 font-bold mb-1">سنة الصنع</p>
                            <p class="font-black text-gray-900">{{ $listing->custom_fields_values['year'] }}</p>
                        </div>
                        @endif
                    @endif

                    @if(isset($listing->custom_fields_values['property_type']))
                        <div class="bg-gray-50 p-4 rounded-2xl text-center border border-gray-100">
                            <p class="text-xs text-gray-500 font-bold mb-1">نوع العقار</p>
                            <p class="font-black text-gray-900">
                                {{ __('types.' . $listing->custom_fields_values['property_type']) }}
                            </p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-2xl text-center border border-gray-100">
                            <p class="text-xs text-gray-500 font-bold mb-1">المساحة</p>
                            <p class="font-black text-gray-900">{{ $listing->custom_fields_values['area'] ?? '--' }} م²</p>
                        </div>
                    @endif
                </div>

                {{-- ── 4. الوصف ────────────────────────────────────────────────── --}}
                <div class="prose max-w-none text-gray-700 leading-relaxed bg-gray-50/50 p-6 rounded-2xl border border-gray-100">
                    <h3 class="font-black text-xl mb-4 text-gray-900">وصف الإعلان</h3>
                    {!! $listing->description !!}
                </div>

                {{-- ── 5. التواصل (النسخة الآمنة - Selective Disclosure) ────────── --}}
                <div class="mt-8 p-6 bg-white border-2 border-gray-100 rounded-2xl flex flex-col sm:flex-row items-center justify-between gap-6 shadow-sm">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 bg-gray-50 rounded-full flex items-center justify-center text-2xl font-black text-blue-600 border border-gray-200">
                            {{ mb_substr($listing->user->name, 0, 1) }}
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 font-bold mb-1 uppercase tracking-wider">المعلن</p>
                            <p class="font-black text-gray-900 text-lg">{{ $listing->user->name }}</p>
                            <p class="text-xs text-gray-500 mt-1">عضو منذ {{ $listing->user->created_at->format('Y/m') }}</p>
                        </div>
                    </div>
{{-- ── 6. نظام تقديم العروض (Make an Offer) ──────────────────────── --}}
                <div class="mt-4" x-data="{
                    offerModalOpen: false,
                    offerAmount: '',
                    offerMessage: '',
                    isSubmitting: false,
                    offerFeedback: null,
                    submitOffer() {
                        @guest
                            window.location.href = '{{ route('login') }}';
                            return;
                        @endguest

                        this.isSubmitting = true;
                        this.offerFeedback = null;

                        fetch('{{ route('listings.offer', $listing->id) }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                amount: this.offerAmount,
                                message: this.offerMessage
                            })
                        })
                        .then(async response => {
                            const data = await response.json();
                            if (!response.ok) {
                                throw new Error(data.error || 'حدث خطأ أثناء الإرسال');
                            }
                            return data;
                        })
                        .then(data => {
                            this.offerFeedback = { type: 'success', text: data.success };
                            setTimeout(() => { this.offerModalOpen = false; this.offerFeedback = null; this.offerAmount = ''; this.offerMessage = ''; }, 3000);
                        })
                        .catch(error => {
                            this.offerFeedback = { type: 'error', text: error.message };
                        })
                        .finally(() => {
                            this.isSubmitting = false;
                        });
                    }
                }">
                    
                    {{-- زرار فتح المودال (يظهر فقط لو اليوزر مش هو صاحب الإعلان) --}}
                    @if($listing->user_id !== auth()->id())
                        <button @click="offerModalOpen = true" class="w-full flex justify-center items-center gap-2 bg-indigo-50 border-2 border-indigo-600 text-indigo-700 hover:bg-indigo-600 hover:text-white px-6 py-4 rounded-xl font-black transition-all shadow-sm mt-4">
                            <span>🤝</span> قدم عرض سعر للبائع
                        </button>
                    @endif

                    {{-- الـ Modal (النافذة المنبثقة المخفية) --}}
                    <div x-show="offerModalOpen" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm" x-transition>
                        <div @click.away="offerModalOpen = false" class="bg-white rounded-3xl shadow-xl w-full max-w-md overflow-hidden p-6 transform transition-all">
                            <h3 class="text-2xl font-black text-gray-900 mb-2">تقديم عرض سعر</h3>
                            <p class="text-gray-500 mb-6 text-sm">البائع طالب {{ number_format($listing->price) }} ج.م، اكتب سعرك المناسب.</p>

                            {{-- رسائل النجاح أو الخطأ --}}
                            <template x-if="offerFeedback">
                                <div :class="offerFeedback.type === 'success' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-red-50 text-red-700 border-red-200'" class="p-4 rounded-xl border mb-4 font-bold text-sm" x-text="offerFeedback.text"></div>
                            </template>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-1">السعر المقترح (ج.م)</label>
                                    <input type="number" x-model="offerAmount" class="w-full bg-gray-50 border border-gray-200 text-gray-900 rounded-xl px-4 py-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 font-bold text-lg" placeholder="اكتب سعرك هنا...">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-1">رسالة للبائع (اختياري)</label>
                                    <textarea x-model="offerMessage" class="w-full bg-gray-50 border border-gray-200 text-gray-900 rounded-xl px-4 py-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 resize-none h-24" placeholder="مثال: أنا جاهز للشراء اليوم لو وافقت على السعر..."></textarea>
                                </div>
                            </div>

                            <div class="mt-8 flex gap-3">
                                <button @click="submitOffer()" :disabled="isSubmitting || !offerAmount" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-3 rounded-xl font-bold transition disabled:opacity-50 disabled:cursor-not-allowed">
                                    <span x-show="!isSubmitting">إرسال العرض</span>
                                    <span x-show="isSubmitting">جاري الإرسال...</span>
                                </button>
                                <button @click="offerModalOpen = false" :disabled="isSubmitting" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 py-3 rounded-xl font-bold transition">
                                    إلغاء
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                    {{-- 🟢 جزء أزرار التواصل المُدار بواسطة Alpine.js لمنع الروبوتات --}}
                    <div class="flex w-full sm:w-auto gap-3" x-data="{
                        revealed: false,
                        phone: '',
                        whatsappUrl: '',
                        loading: false,
                        revealPhone() {
                            {{-- لو اليوزر مش مسجل دخول، هنحوله لصفحة اللوجن فوراً --}}
                            @guest
                                window.location.href = '{{ route('login') }}';
                                return;
                            @endguest

                            this.loading = true;
                            
                            {{-- إرسال طلب للسيرفر لجلب الرقم وتسجيل النقرة --}}
                            fetch('{{ route('listings.reveal-phone', $listing->id) }}', {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/json'
                                }
                            })
                            .then(response => {
                                if (response.status === 401) {
                                    window.location.href = '{{ route('login') }}';
                                    throw new Error('يرجى تسجيل الدخول');
                                }
                                return response.json();
                            })
                            .then(data => {
                                if(data.phone) {
                                    this.phone = data.phone;
                                    this.whatsappUrl = data.whatsapp_url;
                                    this.revealed = true;
                                }
                            })
                            .catch(err => console.error('Error:', err))
                            .finally(() => this.loading = false);
                        }
                    }">
                        {{-- 🔒 الزر الأولي: إظهار الرقم (يظهر قبل الضغط) --}}
                        <div x-show="!revealed" class="w-full flex flex-col items-center">
                            <button @click="revealPhone()" :disabled="loading" class="w-full flex justify-center items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-bold transition-all shadow-sm hover:shadow-md">
                                <span x-show="!loading">🔒 إظهار رقم التواصل</span>
                                <span x-show="loading" style="display: none;">جاري التحميل... ⏳</span>
                            </button>
                            
                            @guest
                                <p class="text-xs text-gray-500 mt-2 font-bold">
                                    يجب تسجيل الدخول لرؤية الرقم.
                                </p>
                            @endguest
                        </div>

                        {{-- 🔓 الأزرار الفعلية (تظهر فقط بعد الضغط واستلام البيانات من السيرفر) --}}
                        <div x-show="revealed" style="display: none;" class="flex w-full gap-3">
                            <a :href="whatsappUrl" target="_blank" class="flex-1 flex items-center justify-center gap-2 bg-[#25D366] hover:bg-[#20bd5a] text-white px-6 py-3 rounded-xl font-bold transition-all shadow-sm hover:shadow-md">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.393.86s.274.072.376-.043c.101-.116.433-.506.549-.68.116-.173.231-.145.39-.087s1.011.477 1.184.564.289.13.332.202c.045.072.045.419-.1.824z"></path></svg>
                                واتساب
                            </a>

                            <a :href="'tel:' + phone" class="flex-1 flex items-center justify-center gap-2 bg-gray-900 hover:bg-gray-800 text-white px-6 py-3 rounded-xl font-bold transition-all shadow-sm hover:shadow-md">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                اتصال
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>