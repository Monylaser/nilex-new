@extends('layouts.frontend')

@section('title', __('ui.payment.page_title_failed') . ' | Nilex')

@section('content')
    <main class="min-h-screen bg-nilex-bg pt-24 pb-16">
        <section class="mx-auto w-full max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-3xl border border-nilex/10 bg-white p-6 shadow-nilex sm:p-10">
                <div class="mx-auto max-w-2xl text-center">
                    <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-rose-100 text-4xl text-rose-600">
                        <span aria-hidden="true">✕</span>
                    </div>

                    <h1 class="text-2xl font-extrabold text-nilex-ink sm:text-3xl">
                        {{ __('ui.payment.failed_title') }}
                    </h1>
                    <p class="mt-3 text-sm text-zinc-600 sm:text-base">
                        {{ __('ui.payment.failed_body') }}
                    </p>

                    <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                        <a href="{{ route('home') }}" class="btn-nilex-primary inline-flex items-center justify-center rounded-xl px-6 py-3 text-sm font-bold">
                            {{ __('ui.payment.failed_cta') }}
                        </a>
                        <a
                            href="{{ route('pricing') }}"
                            class="inline-flex items-center justify-center rounded-xl border border-nilex-teal px-6 py-3 text-sm font-bold text-nilex-teal transition hover:bg-nilex-teal hover:text-white"
                        >
                            {{ __('ui.payment.failed_retry_cta') }}
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </main>
@endsection
