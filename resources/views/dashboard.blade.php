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
                    <a href="{{ route('listings.create') }}" class="text-blue-600 font-bold text-sm hover:text-blue-800 transition-colors">+ إضافة إعلان جديد</a>
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
<tr class="hover:bg-gray-50/50 transition-colors duration-200">
    <td class="px-6 py-4">
        {{-- هنا ربطنا العنوان بصفحة التفاصيل --}}
        <a href="{{ route('listings.show', $listing) }}" class="group flex items-center gap-2">
            <span class="font-bold text-gray-800 group-hover:text-blue-600 transition-colors">
                {{ $listing->title }}
            </span>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
            </svg>
        </a>
    </td>
    <td class="px-6 py-4">
        @if($listing->status === 'active')
            <span class="text-emerald-600 bg-emerald-50 px-3 py-1 rounded-full text-xs font-bold ring-1 ring-emerald-100">منشور</span>
        @else
            <span class="text-orange-600 bg-orange-50 px-3 py-1 rounded-full text-xs font-bold ring-1 ring-orange-100">قيد المراجعة</span>
        @endif
    </td>
    <td class="px-6 py-4 font-black text-gray-900">
        {{ number_format($listing->price) }} <span class="text-[10px] text-gray-500 font-bold">ج.م</span>
    </td>
    <td class="px-6 py-4 text-gray-400 text-sm font-medium">
        {{ $listing->created_at->format('Y/m/d') }}
    </td>
</tr>
@empty
<tr>
    <td colspan="4" class="text-center py-16">
        <div class="flex flex-col items-center">
            <div class="bg-gray-50 p-4 rounded-full mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
            </div>
            <p class="text-gray-400 font-bold">مفيش إعلانات لسه.. ابدأ ونزل أول إعلان ليك!</p>
        </div>
    </td>
</tr>
@endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
