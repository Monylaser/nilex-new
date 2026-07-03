<x-guest-layout>

    {{-- ── رأس الصفحة ── --}}
    <div style="margin-bottom:32px; text-align:{{ app()->getLocale() === 'ar' ? 'right' : 'left' }};">
        <h1 style="font-size:24px; font-weight:900; color:#111827; margin:0;">{{ __('ui.auth.login_heading') }}</h1>
        <p style="color:#6b7280; font-size:14px; margin:4px 0 0;">{{ __('ui.auth.login_subtitle') }}</p>
    </div>

    {{-- ── تبديل: موبايل / إيميل ── --}}
    <div style="display:flex; background:#f3f4f6; border-radius:12px; padding:4px; margin-bottom:24px;">
        <button type="button" class="tab-btn active" onclick="switchTab('phone')" id="tab-phone">
            {{ __('ui.auth.tab_phone') }}
        </button>
        <button type="button" class="tab-btn" onclick="switchTab('email')" id="tab-email">
            {{ __('ui.auth.tab_email') }}
        </button>
    </div>

    {{-- ── Session Status ── --}}
    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if(config('features.self_service_ads'))
        <x-ad-banner placement="login_page" />
    @endif

    {{-- ── الفورم ── --}}
    <form method="POST" action="{{ route('login') }}" style="display:flex; flex-direction:column; gap:16px;">
        @csrf

        {{-- حقل الموبايل --}}
        <div id="field-phone">
            <label style="display:block; font-size:14px; font-weight:600; color:#374151; margin-bottom:6px;">
                {{ __('ui.auth.label_phone') }}
            </label>
            <div style="position:relative;">
                <span style="position:absolute; right:12px; top:50%; transform:translateY(-50%); color:#9ca3af; font-size:13px; pointer-events:none; user-select:none;">
                    🇪🇬 +20
                </span>
                <input
                    type="tel"
                    name="phone"
                    id="phone"
                    value="{{ old('phone') }}"
                    placeholder="01XXXXXXXXX"
                    class="input-field"
                    style="padding-right:72px; direction:ltr; text-align:left;"
                >
            </div>
            @error('phone')
                <p style="color:#ef4444; font-size:12px; margin:4px 0 0;">{{ $message }}</p>
            @enderror
        </div>

        {{-- حقل الإيميل ── مخفي بالبداية ── --}}
        <div id="field-email" style="display:none;">
            <label style="display:block; font-size:14px; font-weight:600; color:#374151; margin-bottom:6px;">
                {{ __('ui.auth.label_email') }}
            </label>
            <input
                type="email"
                name="email"
                id="email"
                value="{{ old('email') }}"
                placeholder="example@email.com"
                class="input-field"
                style="direction:ltr; text-align:left;"
                autocomplete="username"
            >
            @error('email')
                <p style="color:#ef4444; font-size:12px; margin:4px 0 0;">{{ $message }}</p>
            @enderror
        </div>

        {{-- حقل الباسوورد مع زر العين --}}
        <div>
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:6px;">
                <label style="font-size:14px; font-weight:600; color:#374151;">{{ __('ui.auth.label_password') }}</label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-xs text-nilex-teal no-underline font-semibold">
                        {{ __('ui.auth.forgot_password') }}
                    </a>
                @endif
            </div>
            <div style="position:relative;">
                <input
                    type="password"
                    name="password"
                    id="password"
                    placeholder="••••••••"
                    class="input-field"
                    style="padding-left:44px;"
                    autocomplete="current-password"
                    required
                >
                {{-- زر العين --}}
                <button
                    type="button"
                    onclick="togglePassword()"
                    style="position:absolute; left:12px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:#9ca3af; padding:4px; display:flex; align-items:center;"
                    title="{{ __('ui.auth.toggle_password') }}"
                >
                    <svg id="eye-open" xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    <svg id="eye-closed" xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="display:none;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.477 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                    </svg>
                </button>
            </div>
            @error('password')
                <p style="color:#ef4444; font-size:12px; margin:4px 0 0;">{{ $message }}</p>
            @enderror
        </div>

        {{-- تذكرني --}}
        <div style="display:flex; align-items:center; gap:8px;">
            <input type="checkbox" name="remember" id="remember_me"
                class="w-4 h-4 cursor-pointer accent-nilex-teal">
            <label for="remember_me" style="font-size:14px; color:#6b7280; cursor:pointer; user-select:none;">
                {{ __('ui.auth.remember_me') }}
            </label>
        </div>

        {{-- زر الدخول --}}
        <button type="submit" class="btn-nilex-primary">
            {{ __('ui.auth.login_submit') }}
        </button>

    </form>

    {{-- فاصل --}}
    <div class="divider" style="margin:24px 0;">{{ __('ui.auth.divider_login') }}</div>

    {{-- أزرار السوشيال --}}
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">

        <a href="{{ route('auth.social.redirect', 'google') }}" class="btn-social">
            <svg width="20" height="20" viewBox="0 0 24 24" style="flex-shrink:0;">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
            </svg>
            Google
        </a>

        <a href="{{ route('auth.social.redirect', 'facebook') }}" class="btn-social">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="#1877F2" style="flex-shrink:0;">
                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
            </svg>
            Facebook
        </a>

        <a href="{{ route('auth.social.redirect', 'tiktok') }}" class="btn-social">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" style="flex-shrink:0;">
                <path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1V9.01a6.33 6.33 0 00-.79-.05 6.34 6.34 0 00-6.34 6.34 6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.33-6.34V8.69a8.2 8.2 0 004.79 1.52V6.75a4.85 4.85 0 01-1.02-.06z"/>
            </svg>
            TikTok
        </a>

        <a href="{{ route('auth.social.redirect', 'instagram') }}" class="btn-social">
            <svg width="20" height="20" viewBox="0 0 24 24" style="flex-shrink:0;">
                <defs>
                    <linearGradient id="ig" x1="0%" y1="100%" x2="100%" y2="0%">
                        <stop offset="0%" stop-color="#f09433"/>
                        <stop offset="50%" stop-color="#dc2743"/>
                        <stop offset="100%" stop-color="#bc1888"/>
                    </linearGradient>
                </defs>
                <path fill="url(#ig)" d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>
            </svg>
            Instagram
        </a>

    </div>

    {{-- رابط التسجيل --}}
    <p style="text-align:center; font-size:14px; color:#6b7280; margin-top:24px;">
        {{ __('ui.auth.no_account') }}
        <a href="{{ route('register') }}" class="text-nilex-teal font-bold no-underline">
            {{ __('ui.auth.register_now') }}
        </a>
    </p>

    {{-- ── JavaScript مباشر بدون @push ── --}}
    <script>
        function switchTab(tab) {
            var phoneField = document.getElementById('field-phone');
            var emailField = document.getElementById('field-email');
            var tabPhone   = document.getElementById('tab-phone');
            var tabEmail   = document.getElementById('tab-email');
            var phoneInput = document.getElementById('phone');
            var emailInput = document.getElementById('email');

            if (tab === 'phone') {
                phoneField.style.display = 'block';
                emailField.style.display = 'none';
                tabPhone.classList.add('active');
                tabEmail.classList.remove('active');
                phoneInput.setAttribute('required', '');
                emailInput.removeAttribute('required');
            } else {
                emailField.style.display = 'block';
                phoneField.style.display = 'none';
                tabEmail.classList.add('active');
                tabPhone.classList.remove('active');
                emailInput.setAttribute('required', '');
                phoneInput.removeAttribute('required');
            }
        }

        function togglePassword() {
            var input     = document.getElementById('password');
            var eyeOpen   = document.getElementById('eye-open');
            var eyeClosed = document.getElementById('eye-closed');

            if (input.type === 'password') {
                input.type = 'text';
                eyeOpen.style.display   = 'none';
                eyeClosed.style.display = 'block';
            } else {
                input.type = 'password';
                eyeOpen.style.display   = 'block';
                eyeClosed.style.display = 'none';
            }
        }
    </script>

</x-guest-layout>