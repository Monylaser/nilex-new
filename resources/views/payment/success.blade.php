<x-app-layout>
    <div class="py-12 bg-gray-50 min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full bg-white p-8 rounded-3xl shadow-sm border border-gray-100 text-center">
            <div class="w-20 h-20 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center text-4xl mx-auto mb-6">
                ✅
            </div>
            <h2 class="text-2xl font-black text-gray-900 mb-2">تمت عملية الدفع بنجاح!</h2>
            <p class="text-gray-500 mb-8 font-medium">شكراً لك، تم إضافة النقاط إلى رصيدك بنجاح. يمكنك الآن تمييز إعلاناتك.</p>
            <a href="{{ route('dashboard') }}" class="block w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl transition-all">
                الذهاب للوحة التحكم
            </a>
        </div>
    </div>
</x-app-layout>