<x-guest-layout>
    <div x-data="otpVerification()" x-init="startTimer()" dir="rtl">

        {{-- Header --}}
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 bg-nilex/10 rounded-2xl mb-4">
                <svg class="w-7 h-7 text-nilex" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-black text-zinc-900">تأكيد الحساب</h1>
            <p class="text-zinc-500 text-sm mt-1">أدخل الكود المرسل إلى</p>
            <p class="font-bold text-nilex text-base mt-1">
                {{ auth()->user()->email ?? auth()->user()->phone }}
            </p>
        </div>

        {{-- Status --}}
        @if(session('status'))
            <div class="alert-success mb-5 text-sm" role="status">{{ session('status') }}</div>
        @endif

        {{-- OTP Form --}}
        <form method="POST" action="{{ route('otp.verify') }}" novalidate>
            @csrf

            <div class="mb-6">
                <label class="block text-sm font-semibold text-zinc-700 mb-4 text-center">
                    كود التفعيل (4 أرقام)
                </label>

                {{-- 4 individual boxes --}}
                <div class="flex justify-center gap-3" role="group" aria-label="أدخل رمز التحقق">
                    @for($i = 0; $i < 4; $i++)
                        <input type="text"
                               inputmode="numeric"
                               maxlength="1"
                               dir="ltr"
                               x-model="otp[{{ $i }}]"
                               @input="moveToNext({{ $i }}, $event)"
                               @keydown.backspace="moveToPrev({{ $i }}, $event)"
                               @paste.prevent="handlePaste($event)"
                               class="w-14 h-14 text-center text-2xl font-black rounded-xl border-2 border-zinc-200 focus:border-nilex focus:outline-none focus:ring-2 focus:ring-nilex/15 transition-all bg-zinc-50 focus:bg-white text-zinc-900"
                               {{ $i === 0 ? 'autofocus' : '' }}
                               aria-label="الرقم {{ $i + 1 }}">
                    @endfor
                </div>

                <input type="hidden" name="otp" x-bind:value="otp.join('')">

                @error('otp')
                    <p class="text-red-500 text-xs mt-3 text-center" role="alert">{{ $message }}</p>
                @enderror
            </div>

            {{-- Submit --}}
            <button type="submit"
                    class="btn-primary"
                    aria-label="تأكيد رمز التحقق">
                تأكيد الحساب
            </button>
        </form>

        {{-- Resend --}}
        <div class="mt-5 text-center">
            <template x-if="timer > 0">
                <p class="text-zinc-500 text-sm">
                    إعادة الإرسال خلال
                    <span x-text="timer" class="font-bold text-nilex"></span>
                    ثانية
                </p>
            </template>
            <template x-if="timer === 0">
                <form method="POST" action="{{ route('otp.resend') }}">
                    @csrf
                    <button type="submit"
                            class="text-nilex hover:text-nilex-dark font-bold text-sm transition-colors">
                        لم يصلك الكود؟ إعادة الإرسال
                    </button>
                </form>
            </template>
        </div>

    </div>

    <script>
        function otpVerification() {
            return {
                otp: ['', '', '', ''],
                timer: 60,
                timerInterval: null,

                startTimer() {
                    if (this.timerInterval) clearInterval(this.timerInterval);
                    this.timerInterval = setInterval(() => {
                        if (this.timer > 0) {
                            this.timer--;
                        } else {
                            clearInterval(this.timerInterval);
                        }
                    }, 1000);
                },

                moveToNext(index, event) {
                    this.otp[index] = event.target.value.replace(/[^0-9]/g, '');
                    event.target.value = this.otp[index];

                    if (this.otp[index] && index < 3) {
                        const next = event.target.parentElement.children[index + 1];
                        if (next) next.focus();
                    }

                    if (this.otp.every(d => d !== '')) {
                        setTimeout(() => event.target.closest('form').submit(), 100);
                    }
                },

                moveToPrev(index, event) {
                    if (event.target.value === '' && index > 0) {
                        const prev = event.target.parentElement.children[index - 1];
                        if (prev) prev.focus();
                    }
                },

                handlePaste(event) {
                    const text = (event.clipboardData || window.clipboardData)
                        .getData('text').replace(/[^0-9]/g, '').slice(0, 4);
                    if (!text) return;
                    const inputs = event.target.parentElement.children;
                    [...text].forEach((char, i) => {
                        if (inputs[i]) {
                            this.otp[i] = char;
                            inputs[i].value = char;
                        }
                    });
                    if (text.length === 4) {
                        setTimeout(() => event.target.closest('form').submit(), 100);
                    } else if (inputs[text.length]) {
                        inputs[text.length].focus();
                    }
                }
            }
        }
    </script>
</x-guest-layout>
