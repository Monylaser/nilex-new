@extends('layouts.frontend')

@section('title', '403 - ' . __('ui.errors.403_title') . ' | Nilex')

@section('content')
    <main class="min-h-screen bg-nilex-bg pt-24 pb-16">
        <section class="mx-auto w-full max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-3xl border border-nilex/10 bg-white p-6 shadow-nilex sm:p-10">
                <div class="mx-auto max-w-2xl text-center">
                    <img
                        src="{{ asset('images/logo/download.png') }}"
                        alt="Nilex نايلكس"
                        class="mx-auto mb-6 h-16 w-auto sm:h-20"
                    >

                    <p class="text-6xl font-black leading-none text-nilex-teal sm:text-7xl md:text-8xl">403</p>
                    <h1 class="mt-4 text-2xl font-extrabold text-nilex-ink sm:text-3xl">
                        {{ __('ui.errors.403_title') }}
                    </h1>
                    <p class="mt-3 text-sm text-zinc-600 sm:text-base">
                        {{ __('ui.errors.403_message') }}
                    </p>

                    <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                        <a href="{{ route('home') }}" class="btn-nilex-primary inline-flex items-center justify-center rounded-xl px-6 py-3 text-sm font-bold">
                            {{ __('ui.errors.back_home') }}
                        </a>
                        <a
                            href="mailto:{{ __('ui.errors.support_email') }}"
                            class="inline-flex items-center justify-center rounded-xl border border-nilex-teal px-6 py-3 text-sm font-bold text-nilex-teal transition hover:bg-nilex-teal hover:text-white"
                        >
                            {{ __('ui.errors.contact_us') }}
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </main>
@endsection
