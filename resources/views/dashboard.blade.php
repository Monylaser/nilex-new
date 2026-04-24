<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 leading-tight">
            مرحباً بك، {{ auth()->user()->name }} 👋
        </h2>
    </x-slot>

    <div class="py-12 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            {{-- كروت الإحصائيات السريعة --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
                <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
                    <p class="text-gray-500 font-bold">إعلاناتي</p>
                    <h3 class="text-3xl font-black text-blue-600 mt-2">{{ auth()->user()->listings()->count() }}</h3>
                </div>
                <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
                    <p class="text-gray-500 font-bold">رصيد النقاط</p>
                    <h3 class="text-3xl font-black text-emerald-600 mt-2">{{ auth()->user()->points ?? 0 }}</h3>
                </div>
                <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100 flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 font-bold">الحالة</p>
                        <span class="inline-block mt-2 px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-black">نشط</span>
                    </div>
                </div>
            </div>

            {{-- قائمة إعلاناتي --}}
            <div class="bg-white rounded-3xl shadow-sm overflow-hidden">
                <div class="p-6 border-b flex justify-between items-center">
                    <h3 class="font-black text-xl text-gray-800">إدارة إعلاناتي</h3>
                    <a href="/register" class="text-blue-600 font-bold text-sm">+ إضافة إعلان جديد</a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-right">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-4 text-gray-500 font-bold">الإعلان</th>
                                <th class="px-6 py-4 text-gray-500 font-bold">الحالة</th>
                                <th class="px-6 py-4 text-gray-500 font-bold">السعر</th>
                                <th class="px-6 py-4 text-gray-500 font-bold">التاريخ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse(auth()->user()->listings as $listing)
                            <tr>
                                <td class="px-6 py-4 font-bold text-gray-800">{{ $listing->title }}</td>
                                <td class="px-6 py-4">
                                    @if($listing->status === 'active')
                                        <span class="text-emerald-600 bg-emerald-50 px-3 py-1 rounded-full text-xs font-bold">منشور</span>
                                    @else
                                        <span class="text-orange-600 bg-orange-50 px-3 py-1 rounded-full text-xs font-bold">قيد المراجعة</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 font-bold">{{ number_format($listing->price) }} ج.م</td>
                                <td class="px-6 py-4 text-gray-400 text-sm">{{ $listing->created_at->format('Y/m/d') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-10 text-gray-400 italic">مفيش إعلانات لسه.. ابدأ ونزل أول إعلان ليك!</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
