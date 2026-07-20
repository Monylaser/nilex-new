{{--
  Unified password field with show/hide eye toggle.
  Uses logical CSS (end-* / pe-*) so the icon flips correctly in RTL and LTR.
--}}
@props([
    'name',
    'id' => null,
    'placeholder' => '••••••••',
    'autocomplete' => null,
    'required' => false,
])

@php
    $inputId = $id ?? $name;
@endphp

<div class="relative" x-data="{ show: false }">
    <input
        :type="show ? 'text' : 'password'"
        name="{{ $name }}"
        id="{{ $inputId }}"
        placeholder="{{ $placeholder }}"
        @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        @if($required) required @endif
        {{ $attributes->class(['pe-12']) }}
    >

    <button
        type="button"
        @click="show = !show"
        class="absolute end-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-nilex transition-colors p-1"
        aria-label="{{ __('ui.auth.toggle_password') }}"
    >
        <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
        </svg>
        <svg x-show="show" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="display:none;" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.477 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
        </svg>
    </button>
</div>
