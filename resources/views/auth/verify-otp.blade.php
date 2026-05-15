<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600 text-right font-['Cairo']">
        <h2 class="text-2xl font-black text-gray-900 mb-2">تأكيد الحساب 🔐</h2>
        <p>لقد أرسلنا كود تفعيل مكون من 4 أرقام إلى بريدك الإلكتروني.</p>
        <p class="font-bold text-blue-600 mt-1">{{ auth()->user()->email }}</p>
    </div>

    @if (session('status'))
        <div class="mb-4 font-bold text-sm text-emerald-600 text-right bg-emerald-50 p-3 rounded-lg border border-emerald-100">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('otp.verify') }}" class="text-right font-['Cairo']">
        @csrf

        <div>
            <x-input-label for="otp" value="كود التفعيل" />
            
            <x-text-input id="otp" class="block mt-1 w-full text-center text-2xl tracking-widest font-black" 
                          type="text" 
                          name="otp" 
                          maxlength="4" 
                          required 
                          autofocus 
                          autocomplete="one-time-code" 
                          placeholder="••••" />
            
            <x-input-error :messages="$errors->get('otp')" class="mt-2" />
        </div>

        <div class="mt-6 flex flex-col gap-3">
            <x-primary-button class="w-full justify-center py-3 text-lg">
                تأكيد الحساب
            </x-primary-button>
        </div>
    </form>

    <form method="POST" action="{{ route('otp.resend') }}" class="mt-4 text-center font-['Cairo']">
        @csrf
        <p class="text-sm text-gray-600">
            لم يصلك الكود؟ 
            <button type="submit" class="underline text-blue-600 hover:text-blue-900 font-bold">
                إعادة الإرسال
            </button>
        </p>
    </form>
</x-guest-layout>