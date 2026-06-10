{{-- resources/views/profile/edit.blade.php --}}

<x-app-layout>

    <div class="bg-zinc-50 min-h-screen py-8" dir="rtl"
         x-data="{ deleteOpen: false }">
        <div class="max-w-3xl mx-auto px-4 space-y-5">

            {{-- ══════════════════════════════════════════════════════════════
                 TRUST CARD — Account health indicators
            ══════════════════════════════════════════════════════════════ --}}
            <div class="bg-white rounded-2xl border border-zinc-100 p-5" style="box-shadow:0 1px 6px rgba(0,0,0,0.05);">
                <div class="flex flex-col sm:flex-row sm:items-center gap-4">

                    {{-- Avatar --}}
                    <div class="relative self-start shrink-0">
                        <img id="avatar-preview"
                             src="{{ $user->avatar ? Storage::url($user->avatar) : 'https://ui-avatars.com/api/?name='.urlencode($user->name).'&background=1D9E75&color=fff&size=80' }}"
                             class="w-20 h-20 rounded-2xl object-cover border-2 border-zinc-100"
                             alt="{{ $user->name }}">
                    </div>

                    {{-- Identity --}}
                    <div class="flex-1 min-w-0">
                        <h2 class="text-xl font-black text-zinc-900 leading-tight">{{ $user->name }}</h2>
                        <p class="text-sm text-zinc-400 mt-0.5">{{ $user->email }}</p>

                        {{-- Trust badges strip --}}
                        <div class="flex flex-wrap gap-2 mt-3">
                            {{-- Phone verified --}}
                            @if($user->is_phone_verified ?? false)
                                <span class="inline-flex items-center gap-1.5 text-xs font-bold text-nilex bg-nilex/8 px-2.5 py-1 rounded-full">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                    رقم موثق
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-zinc-400 bg-zinc-100 px-2.5 py-1 rounded-full">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                                    رقم غير موثق
                                </span>
                            @endif

                            {{-- Email verified --}}
                            @if($user->hasVerifiedEmail())
                                <span class="inline-flex items-center gap-1.5 text-xs font-bold text-nilex bg-nilex/8 px-2.5 py-1 rounded-full">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                    بريد موثق
                                </span>
                            @endif

                            {{-- Member since --}}
                            <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-zinc-500 bg-zinc-100 px-2.5 py-1 rounded-full">
                                <svg class="w-3 h-3 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                عضو منذ {{ $user->created_at->diffForHumans() }}
                            </span>

                            {{-- Points --}}
                            <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-amber-600 bg-amber-50 px-2.5 py-1 rounded-full">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a1 1 0 011 1v1.323l3.954 1.582 1.599-.8a1 1 0 01.894 1.79l-1.233.616 1.738 5.42a1 1 0 01-.285 1.05A3.989 3.989 0 0115 14a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.715-5.349L11 6.477V16h2a1 1 0 110 2H7a1 1 0 110-2h2V6.477L6.237 7.582l1.715 5.349a1 1 0 01-.285 1.05A3.989 3.989 0 015 15a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.738-5.42-1.233-.617a1 1 0 01.894-1.788l1.599.799L9 4.323V3a1 1 0 011-1z"/></svg>
                                {{ number_format($user->points ?? 0) }} نقطة
                            </span>
                        </div>
                    </div>

                    {{-- Rating placeholder --}}
                    <div class="shrink-0 text-center border-s border-zinc-100 ps-5 hidden sm:block">
                        <div class="flex items-center justify-center gap-0.5 mb-1">
                            @for($i = 1; $i <= 5; $i++)
                                <svg class="w-4 h-4 text-zinc-200" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            @endfor
                        </div>
                        <p class="text-xs text-zinc-400 font-medium">لا توجد تقييمات</p>
                        <p class="text-[10px] text-zinc-300 mt-0.5">قريباً</p>
                    </div>

                </div>
            </div>

            {{-- ══════════════════════════════════════════════════════════════
                 PROFILE INFORMATION FORM
            ══════════════════════════════════════════════════════════════ --}}
            <div class="bg-white rounded-2xl border border-zinc-100 p-5" style="box-shadow:0 1px 6px rgba(0,0,0,0.05);">
                <h3 class="text-base font-black text-zinc-900 mb-5">معلومات الحساب</h3>

                @if(session('status') === 'profile-updated')
                    <div class="alert-success mb-5 text-sm">تم تحديث الملف الشخصي بنجاح</div>
                @endif

                <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    @method('PATCH')

                    {{-- Avatar upload --}}
                    <div class="flex items-center gap-4">
                        <div class="relative shrink-0">
                            <img id="avatar-preview-form"
                                 src="{{ $user->avatar ? Storage::url($user->avatar) : 'https://ui-avatars.com/api/?name='.urlencode($user->name).'&background=1D9E75&color=fff&size=64' }}"
                                 class="w-16 h-16 rounded-2xl object-cover border border-zinc-200"
                                 alt="">
                            <label for="avatar"
                                   class="absolute -bottom-1 -start-1 bg-white border border-zinc-200 rounded-xl p-1.5 cursor-pointer hover:bg-zinc-50 transition-colors">
                                <svg class="w-3.5 h-3.5 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9zM15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </label>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-zinc-700">الصورة الشخصية</p>
                            <p class="text-xs text-zinc-400 mt-0.5">JPG / PNG / WebP — حد أقصى 2MB</p>
                        </div>
                        <input type="file" name="avatar" id="avatar" class="hidden" accept="image/*"
                               onchange="document.getElementById('avatar-preview-form').src = URL.createObjectURL(this.files[0])">
                    </div>
                    @error('avatar')
                        <p class="text-red-500 text-xs -mt-2">{{ $message }}</p>
                    @enderror

                    {{-- Name --}}
                    <div>
                        <label class="block text-sm font-semibold text-zinc-700 mb-1.5">الاسم الكامل</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}"
                               class="w-full border border-zinc-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-nilex focus:ring-2 focus:ring-nilex/15 transition-all">
                        @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Phone --}}
                    <div>
                        <label class="block text-sm font-semibold text-zinc-700 mb-1.5">
                            رقم الموبايل
                            @if($user->is_phone_verified ?? false)
                                <span class="text-nilex font-bold text-xs">· موثق</span>
                            @endif
                        </label>
                        <div class="relative">
                            <span class="absolute end-3.5 top-1/2 -translate-y-1/2 text-zinc-400 text-sm select-none">🇪🇬 +20</span>
                            <input type="tel" name="phone" value="{{ old('phone', $user->phone) }}"
                                   placeholder="01XXXXXXXXX" dir="ltr"
                                   class="w-full border border-zinc-200 rounded-xl px-4 py-2.5 pe-20 text-sm focus:outline-none focus:border-nilex focus:ring-2 focus:ring-nilex/15 transition-all text-start">
                        </div>
                        @error('phone') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- WhatsApp --}}
                    <div>
                        <label class="block text-sm font-semibold text-zinc-700 mb-1.5">
                            رقم الواتساب
                            <span class="text-zinc-400 font-normal text-xs">(لو مختلف عن الموبايل)</span>
                        </label>
                        <div class="relative">
                            <span class="absolute end-3.5 top-1/2 -translate-y-1/2" style="color:#25D366;">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                            </span>
                            <input type="tel" name="whatsapp" value="{{ old('whatsapp', $user->whatsapp ?? $user->phone) }}"
                                   placeholder="01XXXXXXXXX" dir="ltr"
                                   class="w-full border border-zinc-200 rounded-xl px-4 py-2.5 pe-12 text-sm focus:outline-none focus:border-nilex focus:ring-2 focus:ring-nilex/15 transition-all text-start">
                        </div>
                        @error('whatsapp') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Governorate + City --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-semibold text-zinc-700 mb-1.5">المحافظة</label>
                            <input type="text" name="governorate" value="{{ old('governorate', $user->governorate) }}"
                                   placeholder="مثال: القاهرة"
                                   class="w-full border border-zinc-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-nilex focus:ring-2 focus:ring-nilex/15 transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-zinc-700 mb-1.5">المدينة</label>
                            <input type="text" name="city" value="{{ old('city', $user->city) }}"
                                   placeholder="مثال: مدينة نصر"
                                   class="w-full border border-zinc-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-nilex focus:ring-2 focus:ring-nilex/15 transition-all">
                        </div>
                    </div>

                    {{-- Bio --}}
                    <div>
                        <label class="block text-sm font-semibold text-zinc-700 mb-1.5">
                            نبذة شخصية
                            <span class="text-zinc-400 font-normal text-xs">(اختياري)</span>
                        </label>
                        <textarea name="bio" rows="3" placeholder="اكتب نبذة قصيرة عنك..."
                                  class="w-full border border-zinc-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-nilex focus:ring-2 focus:ring-nilex/15 resize-none transition-all">{{ old('bio', $user->bio) }}</textarea>
                        @error('bio') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <button type="submit"
                            class="w-full bg-nilex hover:bg-nilex-dark text-white font-bold py-3 rounded-xl transition-all active:scale-[0.99] text-sm"
                            style="box-shadow:0 4px 14px rgba(29,158,117,0.22);">
                        حفظ التغييرات
                    </button>
                </form>
            </div>

            {{-- ══════════════════════════════════════════════════════════════
                 RATINGS SECTION (UI ready — awaiting backend)
            ══════════════════════════════════════════════════════════════ --}}
            <div class="bg-white rounded-2xl border border-zinc-100 p-5" style="box-shadow:0 1px 6px rgba(0,0,0,0.05);">
                <h3 class="text-base font-black text-zinc-900 mb-4">تقييماتي</h3>
                <div class="flex flex-col sm:flex-row sm:items-center gap-5">
                    {{-- Average rating display --}}
                    <div class="text-center shrink-0 sm:border-e border-zinc-100 sm:pe-5">
                        <p class="text-4xl font-black text-zinc-300 leading-none">—</p>
                        <div class="flex items-center justify-center gap-0.5 my-2">
                            @for($i = 1; $i <= 5; $i++)
                                <svg class="w-4 h-4 text-zinc-200" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            @endfor
                        </div>
                        <p class="text-xs text-zinc-400 font-medium">0 تقييم</p>
                    </div>
                    {{-- Explanation --}}
                    <div class="flex-1">
                        <p class="text-sm text-zinc-500 leading-relaxed">
                            نظام التقييمات قادم قريباً. بعد إتمام كل صفقة، سيتمكن المشترون من تقييم تجربتهم معك.
                        </p>
                        <div class="flex flex-wrap gap-2 mt-3">
                            <span class="flex items-center gap-1.5 text-xs font-semibold text-nilex bg-nilex/5 px-3 py-1.5 rounded-full">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                رد سريع على العروض
                            </span>
                            <span class="flex items-center gap-1.5 text-xs font-semibold text-nilex bg-nilex/5 px-3 py-1.5 rounded-full">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                توثيق رقم الهاتف
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ══════════════════════════════════════════════════════════════
                 PASSWORD CHANGE
            ══════════════════════════════════════════════════════════════ --}}
            <div class="bg-white rounded-2xl border border-zinc-100 p-5" style="box-shadow:0 1px 6px rgba(0,0,0,0.05);">
                <h3 class="text-base font-black text-zinc-900 mb-5">تغيير كلمة المرور</h3>

                @if(session('status') === 'password-updated')
                    <div class="alert-success mb-5 text-sm">تم تغيير كلمة المرور بنجاح</div>
                @endif

                <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-sm font-semibold text-zinc-700 mb-1.5">كلمة المرور الحالية</label>
                        <input type="password" name="current_password" autocomplete="current-password"
                               class="w-full border border-zinc-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-nilex focus:ring-2 focus:ring-nilex/15 transition-all">
                        @error('current_password', 'updatePassword')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-zinc-700 mb-1.5">كلمة المرور الجديدة</label>
                        <input type="password" name="password" autocomplete="new-password"
                               class="w-full border border-zinc-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-nilex focus:ring-2 focus:ring-nilex/15 transition-all">
                        @error('password', 'updatePassword')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-zinc-700 mb-1.5">تأكيد كلمة المرور الجديدة</label>
                        <input type="password" name="password_confirmation" autocomplete="new-password"
                               class="w-full border border-zinc-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-nilex focus:ring-2 focus:ring-nilex/15 transition-all">
                    </div>

                    <button type="submit"
                            class="w-full bg-zinc-900 hover:bg-zinc-800 text-white font-bold py-3 rounded-xl transition-all active:scale-[0.99] text-sm">
                        تغيير كلمة المرور
                    </button>
                </form>
            </div>

            {{-- ══════════════════════════════════════════════════════════════
                 DANGER ZONE — Delete account
            ══════════════════════════════════════════════════════════════ --}}
            <div class="bg-white rounded-2xl border border-red-100 p-5" style="box-shadow:0 1px 6px rgba(0,0,0,0.05);">
                <h3 class="text-base font-black text-red-600 mb-1">حذف الحساب</h3>
                <p class="text-sm text-zinc-500 mb-4">بعد حذف الحساب، جميع البيانات تُمسح نهائياً ولا يمكن التراجع.</p>
                <button @click="deleteOpen = true"
                        class="inline-flex items-center gap-2 bg-red-50 hover:bg-red-100 text-red-600 font-bold px-5 py-2.5 rounded-xl text-sm transition-all active:scale-95">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    حذف حسابي نهائياً
                </button>
            </div>

        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════
         DELETE ACCOUNT MODAL (Alpine.js)
    ══════════════════════════════════════════════════════════════ --}}
    <div x-show="deleteOpen" style="display:none;"
         class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4 bg-zinc-900/50 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         dir="rtl">
        <div @click.away="deleteOpen = false"
             class="bg-white rounded-2xl w-full max-w-md p-6"
             style="box-shadow:0 20px 60px rgba(0,0,0,0.15);"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-y-4 opacity-0" x-transition:enter-end="translate-y-0 opacity-100">

            <div class="flex items-start justify-between mb-4">
                <div>
                    <h3 class="text-lg font-black text-zinc-900">تأكيد حذف الحساب</h3>
                    <p class="text-sm text-zinc-500 mt-1">هذا الإجراء لا يمكن التراجع عنه.</p>
                </div>
                <button @click="deleteOpen = false" class="text-zinc-400 hover:text-zinc-600 transition-colors p-1 shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form method="POST" action="{{ route('profile.destroy') }}" class="space-y-4">
                @csrf
                @method('DELETE')

                <div>
                    <label class="block text-sm font-semibold text-zinc-700 mb-1.5">أدخل كلمة المرور للتأكيد</label>
                    <input type="password" name="password" placeholder="كلمة المرور"
                           class="w-full border border-zinc-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-red-400 focus:ring-2 focus:ring-red-400/15 transition-all">
                    @error('password', 'userDeletion')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex gap-3">
                    <button type="submit"
                            class="flex-1 bg-red-600 hover:bg-red-700 text-white font-bold py-3 rounded-xl text-sm transition-all active:scale-95">
                        تأكيد الحذف
                    </button>
                    <button type="button" @click="deleteOpen = false"
                            class="flex-1 bg-zinc-100 hover:bg-zinc-200 text-zinc-700 font-bold py-3 rounded-xl text-sm transition-all">
                        إلغاء
                    </button>
                </div>
            </form>
        </div>
    </div>

</x-app-layout>
