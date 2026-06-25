<x-guest-layout>
    <div dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

        {{-- Header --}}
        <div class="mb-7 {{ app()->getLocale() === 'ar' ? 'text-right' : 'text-left' }}">
            <div class="inline-flex items-center justify-center w-12 h-12 bg-nilex/10 rounded-2xl mb-3">
                <svg class="w-6 h-6 text-nilex" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-black text-zinc-900">{{ __('ui.auth.forgot_heading') }}</h1>
            <p class="text-zinc-500 text-sm mt-1">
                {{ __('ui.auth.forgot_subtitle') }}
            </p>
        </div>

        {{-- Session status --}}
        @if(session('status'))
            <div class="alert-success mb-5 text-sm" role="status">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="space-y-4" novalidate>
            @csrf

            <div>
                <label for="email" class="block text-sm font-semibold text-zinc-700 mb-1.5">
                    {{ __('ui.auth.label_email') }}
                </label>
                <input type="email"
                       id="email"
                       name="email"
                       value="{{ old('email') }}"
                       placeholder="example@email.com"
                       class="input-field"
                       style="direction:ltr; text-align:left;"
                       required
                       autocomplete="email"
                       autofocus>
                @error('email')
                    <p class="text-red-500 text-xs mt-1" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="btn-primary">
                {{ __('ui.auth.forgot_submit') }}
            </button>
        </form>

        <p class="text-center text-sm text-zinc-500 mt-6">
            {{ __('ui.auth.remembered') }}
            <a href="{{ route('login') }}" style="color:#1D9E75; font-weight:700; text-decoration:none;">
                {{ __('ui.auth.back_to_login') }}
            </a>
        </p>

    </div>
</x-guest-layout>
