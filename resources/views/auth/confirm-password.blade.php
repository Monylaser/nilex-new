<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
    </div>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Password')" />

            <x-password-input
                name="password"
                id="password"
                autocomplete="current-password"
                required
                class="block mt-1 w-full px-4 py-3 bg-white border border-zinc-200 rounded-xl text-zinc-900 placeholder-zinc-400 text-sm transition-all duration-200 focus:outline-none focus:border-nilex focus:ring-2 focus:ring-nilex/20"
            />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex justify-end mt-4">
            <x-primary-button>
                {{ __('Confirm') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
