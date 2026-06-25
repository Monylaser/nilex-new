<x-guest-layout>
    <div dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

        {{-- Header --}}
        <div class="mb-7 {{ app()->getLocale() === 'ar' ? 'text-right' : 'text-left' }}">
            <div class="inline-flex items-center justify-center w-12 h-12 bg-nilex/10 rounded-2xl mb-3">
                <svg class="w-6 h-6 text-nilex" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-black text-zinc-900">{{ __('ui.auth.reset_heading') }}</h1>
            <p class="text-zinc-500 text-sm mt-1">{{ __('ui.auth.reset_subtitle') }}</p>
        </div>

        <form method="POST" action="{{ route('password.store') }}" class="space-y-4" novalidate>
            @csrf

            {{-- Hidden token --}}
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            {{-- Email --}}
            <div>
                <label for="email" class="block text-sm font-semibold text-zinc-700 mb-1.5">
                    {{ __('ui.auth.label_email') }}
                </label>
                <input type="email"
                       id="email"
                       name="email"
                       value="{{ old('email', $request->email) }}"
                       placeholder="example@email.com"
                       class="input-field"
                       style="direction:ltr; text-align:left;"
                       required
                       autocomplete="username"
                       autofocus>
                @error('email')
                    <p class="text-red-500 text-xs mt-1" role="alert">{{ $message }}</p>
                @enderror
            </div>

            {{-- New password --}}
            <div>
                <label for="password" class="block text-sm font-semibold text-zinc-700 mb-1.5">
                    {{ __('ui.auth.label_new_password') }}
                </label>
                <input type="password"
                       id="password"
                       name="password"
                       placeholder="••••••••"
                       class="input-field"
                       required
                       autocomplete="new-password">
                @error('password')
                    <p class="text-red-500 text-xs mt-1" role="alert">{{ $message }}</p>
                @enderror
            </div>

            {{-- Confirm password --}}
            <div>
                <label for="password_confirmation" class="block text-sm font-semibold text-zinc-700 mb-1.5">
                    {{ __('ui.auth.label_password_confirm') }}
                </label>
                <input type="password"
                       id="password_confirmation"
                       name="password_confirmation"
                       placeholder="••••••••"
                       class="input-field"
                       required
                       autocomplete="new-password">
                @error('password_confirmation')
                    <p class="text-red-500 text-xs mt-1" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="btn-primary">
                {{ __('ui.auth.reset_submit') }}
            </button>
        </form>

    </div>
</x-guest-layout>
