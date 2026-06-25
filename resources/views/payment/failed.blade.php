<x-app-layout>
    <div class="py-12 bg-gray-50 min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full bg-white p-8 rounded-3xl shadow-sm border border-gray-100 text-center">
            <div class="w-20 h-20 bg-red-100 text-red-600 rounded-full flex items-center justify-center text-4xl mx-auto mb-6">
                ❌
            </div>
            <h2 class="text-2xl font-black text-gray-900 mb-2">{{ __('ui.payment.failed_title') }}</h2>
            <p class="text-gray-500 mb-8 font-medium">{{ __('ui.payment.failed_body') }}</p>
            <a href="{{ route('dashboard') }}" class="block w-full bg-gray-900 hover:bg-black text-white font-bold py-3 rounded-xl transition-all">
                {{ __('ui.payment.failed_cta') }}
            </a>
        </div>
    </div>
</x-app-layout>