<x-guest-layout>
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
<x-guest-layout>
    {{-- ── الخلفية المتحركة (تقدر تغير الصورة من مجلد images) ── --}}
    <div class="fixed inset-0 z-0">
        <img src="{{ asset('images/auth-bg.jpg') }}" alt="Background" class="w-full h-full object-cover filter brightness-50">
        <div class="absolute inset-0 bg-gradient-to-br from-blue-900/60 to-black/80"></div>
    </div>

    <div class="min-h-screen flex items-center justify-center relative z-10 p-4">
        {{-- ── كارت التسجيل الزجاجي (Glassmorphism) ── --}}
        <div class="w-full max-w-lg bg-white/10 backdrop-blur-xl border border-white/20 p-8 sm:p-10 rounded-[2.5rem] shadow-2xl">
            
            <div class="text-center mb-8">
                <h2 class="text-3xl font-black text-white tracking-tight">ابدأ رحلتك الآن 🚀</h2>
                <p class="text-gray-300 mt-2 text-sm font-medium">سجل حسابك بالموبايل، الإيميل أو السوشيال ميديا</p>
            </div>

            {{-- ── أزرار السوشيال ميديا ── --}}
            <div class="grid grid-cols-2 gap-3 mb-6">
                <a href="{{ url('/auth/google') }}" class="flex items-center justify-center gap-2 bg-white/20 hover:bg-white/30 text-white py-3 rounded-2xl transition font-bold border border-white/10 shadow-inner">
                    <img src="https://www.svgrepo.com/show/475656/google-color.svg" class="w-5 h-5" alt="Google"> جوجل
                </a>
                <a href="{{ url('/auth/tiktok') }}" class="flex items-center justify-center gap-2 bg-black/60 hover:bg-black/80 text-white py-3 rounded-2xl transition font-bold border border-white/10 shadow-inner">
                    <img src="https://www.svgrepo.com/show/513020/tiktok-logo.svg" class="w-5 h-5 filter invert" alt="TikTok"> تيك توك
                </a>
                <a href="{{ url('/auth/apple') }}" class="flex items-center justify-center gap-2 bg-white/20 hover:bg-white/30 text-white py-3 rounded-2xl transition font-bold border border-white/10 shadow-inner col-span-2">
                    <img src="https://www.svgrepo.com/show/448270/instagram.svg" class="w-5 h-5 filter invert" alt="Insta"> التسجيل بواسطة إنستجرام
                </a>
            </div>

            <div class="flex items-center my-6">
                <div class="flex-grow border-t border-white/20"></div>
                <span class="px-3 text-white/60 text-sm font-bold">أو أدخل بياناتك</span>
                <div class="flex-grow border-t border-white/20"></div>
            </div>

            {{-- ── فورم التسجيل (موبايل أو إيميل) ── --}}
            <form method="POST" action="{{ route('register') }}" class="space-y-5">
                @csrf
                
                <div>
                    <input type="text" name="name" required placeholder="الاسم بالكامل" class="w-full bg-white/10 border border-white/20 text-white placeholder-white/60 rounded-2xl px-5 py-4 focus:ring-2 focus:ring-blue-400 focus:bg-white/20 outline-none font-bold transition">
                </div>

                <div>
                    <input type="text" name="contact" required placeholder="رقم الموبايل أو البريد الإلكتروني" class="w-full bg-white/10 border border-white/20 text-white placeholder-white/60 rounded-2xl px-5 py-4 focus:ring-2 focus:ring-blue-400 focus:bg-white/20 outline-none font-bold transition">
                </div>

                {{-- حقل الباسورد مع علامة العين --}}
                <div class="relative">
                    <input type="password" id="passwordField" name="password" required placeholder="كلمة المرور" class="w-full bg-white/10 border border-white/20 text-white placeholder-white/60 rounded-2xl px-5 py-4 focus:ring-2 focus:ring-blue-400 focus:bg-white/20 outline-none font-bold transition pr-12">
                    <button type="button" onclick="togglePassword()" class="absolute right-4 top-1/2 -translate-y-1/2 text-white/60 hover:text-white transition">
                        <svg id="eyeIcon" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    </button>
                </div>

                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-black text-lg py-4 rounded-2xl shadow-[0_0_20px_rgba(37,99,235,0.4)] transition-all hover:scale-[1.02] mt-4">
                    إنشاء حساب وإرسال OTP
                </button>
            </form>

            <p class="text-center text-white/70 mt-6 font-medium">
                لديك حساب؟ <a href="{{ route('login') }}" class="text-blue-400 font-bold hover:underline">تسجيل الدخول</a>
            </p>
        </div>
    </div>

    {{-- كود علامة العين السحري --}}
    <script>
        function togglePassword() {
            const input = document.getElementById('passwordField');
            const icon = document.getElementById('eyeIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>';
            } else {
                input.type = 'password';
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>';
            }
        }
    </script>
</x-guest-layout>
