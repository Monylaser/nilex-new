@extends('layouts.frontend')

@section('title', ($mode ?? 'create') === 'edit' ? __('wizard.edit.page_title') : __('wizard.page_title'))

@push('styles')
<style>
    [x-cloak] { display: none !important; }

    .wizard-input {
        width: 100%;
        border: 1.5px solid #e5e7eb;
        border-radius: 0.875rem;
        padding: 0.75rem 1rem;
        font-size: 0.95rem;
        background: #fff;
        transition: border-color .2s, box-shadow .2s;
    }
    .wizard-input:focus {
        outline: none;
        border-color: #14A5A8;
        box-shadow: 0 0 0 3px rgba(20,165,168,0.12);
    }
    .wizard-input.has-error {
        border-color: #f87171;
    }

    .drop-zone.dragging {
        border-color: #14A5A8;
        background: #ecfdf5;
    }
</style>
@endpush

@section('content')
@php
    // وضع الويزارد: 'create' (افتراضي) أو 'edit'. متغيّرات التعديل تأخذ قيماً افتراضية
    // آمنة حتى لا يفشل العرض في وضع الإنشاء (حيث لا تُمرَّر من الكنترولر).
    $mode       = $mode ?? 'create';
    $isEdit     = $mode === 'edit';
    $editData   = $editData   ?? null;
    $editImages = $editImages ?? collect();
    $editRootId = $editRootId ?? null;
@endphp
<div class="bg-gray-50 min-h-screen pt-24 pb-20" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" style="font-family:'Cairo',sans-serif;">

    <div class="max-w-3xl mx-4 md:mx-auto"
         x-data="listingWizard()"
         x-init="init()"
         x-cloak>

        {{-- ══════════════════════════════════════════
             PAGE HEADER
        ══════════════════════════════════════════ --}}
        <div class="text-center mb-6">
            <h1 class="text-2xl md:text-3xl font-black text-zinc-900">{{ $isEdit ? __('wizard.edit.header_title') : __('wizard.header_title') }}</h1>
            <p class="text-sm text-zinc-400 mt-1">{{ $isEdit ? __('wizard.edit.header_subtitle') : __('wizard.header_subtitle') }}</p>
        </div>

        @if($isEdit)
        {{-- تنبيه إعادة المراجعة: أي تعديل يعيد الإعلان لقائمة المراجعة --}}
        <div class="mb-6 flex items-start gap-2 bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded-2xl px-4 py-3">
            <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="font-semibold">{{ __('wizard.edit.remoderation_notice') }}</span>
        </div>
        @endif

        {{-- ══════════════════════════════════════════
             PROGRESS BAR
        ══════════════════════════════════════════ --}}
        <div class="mb-8">
            <div class="flex items-center justify-between">
                @php
                    $stepLabels = [
                        1 => __('wizard.steps.category'),
                        2 => __('wizard.steps.details'),
                        3 => __('wizard.steps.images'),
                        4 => __('wizard.steps.review'),
                    ];
                @endphp
                @foreach($stepLabels as $num => $label)
                    {{-- Step node --}}
                    <div class="flex flex-col items-center text-center shrink-0">
                        <div class="w-10 h-10 md:w-11 md:h-11 rounded-full flex items-center justify-center font-bold text-sm border-2 transition-all duration-300"
                             :class="{
                                'bg-nilex-teal border-nilex-teal text-white': currentStep > {{ $num }},
                                'bg-white border-nilex-teal text-nilex-teal ring-4 ring-green-100': currentStep === {{ $num }},
                                'bg-white border-gray-200 text-gray-300': currentStep < {{ $num }},
                             }">
                            <template x-if="currentStep > {{ $num }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                </svg>
                            </template>
                            <span x-show="currentStep <= {{ $num }}">{{ $num }}</span>
                        </div>
                        <span class="hidden md:block text-[11px] font-semibold mt-2 transition-colors"
                              :class="currentStep >= {{ $num }} ? 'text-nilex-teal' : 'text-gray-400'">
                            {{ $label }}
                        </span>
                    </div>

                    {{-- Connecting line --}}
                    @if(!$loop->last)
                        <div class="flex-1 h-0.5 mx-1.5 md:mx-2 rounded-full transition-all duration-300"
                             :class="currentStep > {{ $num }} ? 'bg-nilex-teal' : 'bg-gray-200'"></div>
                    @endif
                @endforeach
            </div>
        </div>

        {{-- ══════════════════════════════════════════
             WIZARD CARD
        ══════════════════════════════════════════ --}}
        <form @submit.prevent="submitForm()" class="bg-white rounded-3xl shadow-xl p-5 md:p-8">

            {{-- Top-level submit error --}}
            <div x-show="submitError" x-cloak
                 class="mb-5 flex items-start gap-2 bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl px-4 py-3">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span x-text="submitError"></span>
            </div>

            {{-- ────────────────────────────────────────
                 STEP 1 — CHOOSE CATEGORY
            ──────────────────────────────────────── --}}
            <div x-show="currentStep === 1"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-x-8"
                 x-transition:enter-end="opacity-100 translate-x-0">

                <h2 class="text-lg font-bold text-zinc-900 mb-1">{{ __('wizard.step1.title') }}</h2>
                <p class="text-sm text-zinc-400 mb-5">{{ __('wizard.step1.subtitle') }}</p>

                <p x-show="errors.category_id" x-cloak
                   class="mb-4 text-sm text-red-600 font-semibold" x-text="errors.category_id"></p>

                @if($isEdit)
                {{-- القسم مقفول بعد النشر: عرض للقراءة فقط (لا تغيير) --}}
                <div class="rounded-2xl border-2 border-gray-200 bg-gray-50 p-5 flex items-center gap-3">
                    <span class="w-11 h-11 rounded-xl bg-white border border-gray-200 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </span>
                    <div>
                        <p class="text-sm font-bold text-zinc-800" x-text="activeCategory ? activeCategory.name : ''"></p>
                        <p class="text-xs text-zinc-500 mt-0.5">{{ __('wizard.edit.category_locked') }}</p>
                    </div>
                </div>
                @else
                {{-- Root categories grid --}}
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    @foreach($categories as $cat)
                        <div @click="selectRoot(categories.find(c => c.id === {{ $cat->id }}))"
                             class="rounded-2xl border-2 p-4 cursor-pointer flex flex-col items-center text-center gap-2 transition-all duration-200 hover:shadow-md hover:scale-105 min-h-[44px]"
                             :class="(selectedRootId === {{ $cat->id }})
                                ? 'border-nilex-teal bg-green-50 ring-2 ring-green-400'
                                : 'border-gray-100 bg-white hover:border-gray-200'">
                            <div class="w-12 h-12 flex items-center justify-center rounded-xl bg-gray-50 overflow-hidden">
                                @php $iconUrl = $cat->getFirstMediaUrl('icon'); @endphp
                                @if($iconUrl)
                                    <img src="{{ $iconUrl }}" alt="{{ $cat->name }}" class="w-10 h-10 object-contain">
                                @else
                                    <span class="text-nilex-teal font-bold text-xl">{{ mb_substr($cat->name, 0, 1) }}</span>
                                @endif
                            </div>
                            <span class="text-[13px] font-bold text-zinc-800 leading-tight">{{ $cat->name }}</span>
                        </div>
                    @endforeach
                </div>

                {{-- Subcategories --}}
                <div x-show="subCategories.length > 0" x-cloak class="mt-7">
                    <h3 class="text-sm font-bold text-zinc-700 mb-3 flex items-center gap-2">
                        <span class="w-1 h-4 rounded-full inline-block bg-nilex-teal"></span>
                        {{ __('wizard.step1.choose_sub') }}
                    </h3>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="sub in subCategories" :key="sub.id">
                            <button type="button" @click="selectSub(sub)"
                                    class="px-4 py-2 rounded-xl border-2 text-sm font-semibold transition-all duration-200 min-h-[44px] flex items-center gap-2"
                                    :class="(formData.category_id == sub.id)
                                        ? 'border-nilex-teal bg-green-50 text-nilex-teal ring-2 ring-green-300'
                                        : 'border-gray-200 text-zinc-600 hover:border-nilex-teal'">
                                <span class="w-8 h-8 flex items-center justify-center shrink-0">
                                    <template x-if="sub.icon_url">
                                        <img :src="sub.icon_url" :alt="sub.name" class="w-10 h-10 object-contain">
                                    </template>
                                    <template x-if="!sub.icon_url">
                                        <span class="text-nilex-teal font-bold text-xl" x-text="sub.name ? [...sub.name][0] : '?'"></span>
                                    </template>
                                </span>
                                <span x-text="sub.name"></span>
                            </button>
                        </template>
                    </div>
                </div>
                @endif
            </div>

            {{-- ────────────────────────────────────────
                 STEP 2 — LISTING DETAILS
            ──────────────────────────────────────── --}}
            <div x-show="currentStep === 2"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-x-8"
                 x-transition:enter-end="opacity-100 translate-x-0">

                <h2 class="text-lg font-bold text-zinc-900 mb-1">{{ __('wizard.step2.title') }}</h2>
                <p class="text-sm text-zinc-400 mb-5">{{ __('wizard.step2.subtitle') }}</p>

                {{-- ────────────────────────────────────────
                     🤖 المساعد الذكي (Gemini) — إضافة فقط
                ──────────────────────────────────────── --}}
                <div class="mb-6 rounded-2xl border border-nilex-teal/30 bg-gradient-to-br from-green-50 to-white p-4">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-lg">🤖</span>
                        <h3 class="text-sm font-bold text-zinc-800">{{ __('wizard.step2.ai_title') }}</h3>
                    </div>
                    <p class="text-xs text-zinc-500 mb-3">{{ __('wizard.step2.ai_desc') }}</p>
                    <div class="flex flex-col sm:flex-row gap-2">
                        <input type="text" x-model="aiPrompt" @keydown.enter.prevent="generateWithAI()"
                               class="wizard-input flex-1"
                               placeholder="{{ __('wizard.step2.ai_placeholder') }}">
                        <button type="button" @click="generateWithAI()"
                                :disabled="aiLoading || aiPrompt.trim().length < 3"
                                class="inline-flex items-center justify-center gap-2 bg-nilex-teal hover:bg-nilex-teal-deep disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold px-5 py-2.5 rounded-xl text-sm min-h-[44px] shrink-0">
                            <svg x-show="aiLoading" x-cloak class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span x-text="aiLoading ? '{{ __('wizard.step2.ai_generating') }}' : '{{ __('wizard.step2.ai_generate_btn') }}'"></span>
                        </button>
                    </div>
                    <div x-show="aiMessage" x-cloak
                         class="mt-3 text-xs font-semibold rounded-xl px-3 py-2"
                         :class="aiMessage && aiMessage.type === 'success'
                            ? 'bg-green-100 text-nilex-teal'
                            : 'bg-amber-50 text-amber-700 border border-amber-200'"
                         x-text="aiMessage ? aiMessage.text : ''"></div>
                </div>

                <div class="space-y-5">
                    {{-- Title --}}
                    <div>
                        <label class="block text-sm font-semibold text-zinc-700 mb-1.5">{{ __('wizard.step2.title_label') }} <span class="text-red-500">*</span></label>
                        <input type="text" x-model="formData.title" maxlength="255"
                               class="wizard-input" :class="errors.title ? 'has-error' : ''"
                               placeholder="{{ __('wizard.step2.title_placeholder') }}">
                        <div class="flex justify-between mt-1">
                            <p x-show="errors.title" x-cloak class="text-xs text-red-600" x-text="errors.title"></p>
                            <p class="text-[11px] text-zinc-400 ms-auto"><span x-text="formData.title.length"></span>/255</p>
                        </div>
                    </div>

                    {{-- Description --}}
                    <div>
                        <label class="block text-sm font-semibold text-zinc-700 mb-1.5">{{ __('wizard.step2.desc_label') }} <span class="text-red-500">*</span></label>
                        <textarea x-model="formData.description" rows="5"
                                  class="wizard-input resize-y" :class="errors.description ? 'has-error' : ''"
                                  placeholder="{{ __('wizard.step2.desc_placeholder') }}"></textarea>
                        <div class="flex justify-between mt-1">
                            <p x-show="errors.description" x-cloak class="text-xs text-red-600" x-text="errors.description"></p>
                            <p class="text-[11px] text-zinc-400 ms-auto"><span x-text="formData.description.length"></span> {{ __('wizard.step2.char_suffix') }}</p>
                        </div>
                    </div>

                    {{-- Condition (جديد/مستعمل) — مخفي لقسم العقارات (لا معنى له هناك) --}}
                    <div x-show="!isRealEstateCategory" x-cloak>
                        <label class="block text-sm font-semibold text-zinc-700 mb-2">{{ __('wizard.step2.condition_label') }} <span class="text-red-500">*</span></label>
                        <div class="grid grid-cols-2 gap-3">
                            <div @click="formData.condition = 'new'"
                                 class="rounded-2xl border-2 p-4 cursor-pointer text-center transition-all duration-200 min-h-[44px]"
                                 :class="formData.condition === 'new' ? 'border-nilex-teal bg-green-50 ring-2 ring-green-300' : 'border-gray-200 hover:border-gray-300'">
                                <div class="text-2xl mb-1">✨</div>
                                <span class="text-sm font-bold text-zinc-800">{{ __('wizard.step2.condition_new') }}</span>
                            </div>
                            <div @click="formData.condition = 'used'"
                                 class="rounded-2xl border-2 p-4 cursor-pointer text-center transition-all duration-200 min-h-[44px]"
                                 :class="formData.condition === 'used' ? 'border-nilex-teal bg-green-50 ring-2 ring-green-300' : 'border-gray-200 hover:border-gray-300'">
                                <div class="text-2xl mb-1">🔄</div>
                                <span class="text-sm font-bold text-zinc-800">{{ __('wizard.step2.condition_used') }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Price --}}
                    <div>
                        <label class="block text-sm font-semibold text-zinc-700 mb-1.5">{{ __('wizard.step2.price_label') }} <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="number" x-model="formData.price" min="0" max="9999999999.99" step="0.01"
                                   class="wizard-input pe-16" :class="errors.price ? 'has-error' : ''"
                                   placeholder="0">
                            <span class="absolute inset-y-0 end-4 flex items-center text-sm text-zinc-400 font-semibold pointer-events-none">{{ __('wizard.common.currency') }}</span>
                        </div>
                        <p x-show="errors.price" x-cloak class="text-xs text-red-600 mt-1" x-text="errors.price"></p>
                    </div>

                    {{-- Price type --}}
                    <div>
                        <label class="block text-sm font-semibold text-zinc-700 mb-2">{{ __('wizard.step2.price_type_label') }}</label>
                        <div class="grid grid-cols-3 gap-2">
                            <template x-for="opt in priceTypes" :key="opt.value">
                                <button type="button" @click="formData.price_type = opt.value"
                                        class="px-2 py-2.5 rounded-xl border-2 text-[13px] font-semibold transition-all duration-200 min-h-[44px]"
                                        :class="formData.price_type === opt.value ? 'border-nilex-teal bg-green-50 text-nilex-teal' : 'border-gray-200 text-zinc-600 hover:border-gray-300'">
                                    <span x-text="opt.label"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    {{-- ────────────────────────────────────────
                         🚗 مواصفات السيارة (قسم السيارات فقط)
                         Brand → Model → Fuel → Transmission
                         dependent dropdowns بنفس نمط المحافظة → المدينة
                    ──────────────────────────────────────── --}}
                    <div x-show="isCarCategory" x-cloak class="pt-2 border-t border-gray-100">
                        <h3 class="text-sm font-bold text-zinc-700 mb-3 mt-3 flex items-center gap-2">
                            <span class="w-1 h-4 rounded-full inline-block bg-nilex-teal"></span>
                            {{ __('wizard.car.section_title') }}
                        </h3>
                        <div class="space-y-4">
                            {{-- Brand (الماركة) --}}
                            <div>
                                <label class="block text-sm font-semibold text-zinc-700 mb-1.5">{{ __('wizard.car.brand_label') }} <span class="text-red-500">*</span></label>
                                <select x-model="formData.car_brand_id" @change="onCarBrandChange()"
                                        class="wizard-input" :class="errors.car_brand_id ? 'has-error' : ''">
                                    <option value="">{{ __('wizard.car.brand_placeholder') }}</option>
                                    <template x-for="brand in carBrands" :key="brand.id">
                                        <option :value="brand.id" x-text="brand.name_ar"></option>
                                    </template>
                                </select>
                                <p x-show="errors.car_brand_id" x-cloak class="text-xs text-red-600 mt-1" x-text="errors.car_brand_id"></p>
                            </div>

                            {{-- Other brand free-text (يظهر عند اختيار "أخرى") --}}
                            <div x-show="selectedBrandIsOther" x-cloak>
                                <label class="block text-sm font-semibold text-zinc-700 mb-1.5">{{ __('wizard.car.brand_other_label') }} <span class="text-red-500">*</span></label>
                                <input type="text" x-model="formData.custom_fields.car_brand_other" maxlength="255"
                                       class="wizard-input" :class="errors.car_brand_other ? 'has-error' : ''"
                                       placeholder="{{ __('wizard.car.brand_other_placeholder') }}">
                                <p x-show="errors.car_brand_other" x-cloak class="text-xs text-red-600 mt-1" x-text="errors.car_brand_other"></p>
                            </div>

                            {{-- Model (الموديل) — filtered by brand --}}
                            <div>
                                <label class="block text-sm font-semibold text-zinc-700 mb-1.5">{{ __('wizard.car.model_label') }} <span class="text-red-500">*</span></label>
                                <select x-model="formData.car_model_id"
                                        :disabled="!formData.car_brand_id"
                                        class="wizard-input disabled:bg-gray-50 disabled:text-zinc-400 disabled:cursor-not-allowed"
                                        :class="errors.car_model_id ? 'has-error' : ''">
                                    <option value="" x-text="formData.car_brand_id ? '{{ __('wizard.car.model_placeholder') }}' : '{{ __('wizard.car.model_placeholder_no_brand') }}'"></option>
                                    <template x-for="model in carModels" :key="model.id">
                                        <option :value="model.id" x-text="model.name_ar"></option>
                                    </template>
                                </select>
                                <p x-show="errors.car_model_id" x-cloak class="text-xs text-red-600 mt-1" x-text="errors.car_model_id"></p>
                            </div>

                            {{-- Fuel (نوع الوقود) --}}
                            <div>
                                <label class="block text-sm font-semibold text-zinc-700 mb-1.5">{{ __('wizard.car.fuel_label') }} <span class="text-red-500">*</span></label>
                                <select x-model="formData.custom_fields.fuel"
                                        class="wizard-input" :class="errors.fuel ? 'has-error' : ''">
                                    <option value="">{{ __('wizard.car.fuel_placeholder') }}</option>
                                    <template x-for="opt in fuelOptions" :key="opt.value">
                                        <option :value="opt.value" x-text="opt.label"></option>
                                    </template>
                                </select>
                                <p x-show="errors.fuel" x-cloak class="text-xs text-red-600 mt-1" x-text="errors.fuel"></p>
                            </div>

                            {{-- Transmission (ناقل الحركة) --}}
                            <div>
                                <label class="block text-sm font-semibold text-zinc-700 mb-1.5">{{ __('wizard.car.transmission_label') }} <span class="text-red-500">*</span></label>
                                <select x-model="formData.custom_fields.transmission"
                                        class="wizard-input" :class="errors.transmission ? 'has-error' : ''">
                                    <option value="">{{ __('wizard.car.transmission_placeholder') }}</option>
                                    <template x-for="opt in transmissionOptions" :key="opt.value">
                                        <option :value="opt.value" x-text="opt.label"></option>
                                    </template>
                                </select>
                                <p x-show="errors.transmission" x-cloak class="text-xs text-red-600 mt-1" x-text="errors.transmission"></p>
                            </div>

                            {{-- Year (سنة الصنع) --}}
                            <div>
                                <label class="block text-sm font-semibold text-zinc-700 mb-1.5">{{ __('wizard.car.year_label') }} <span class="text-red-500">*</span></label>
                                <select x-model="formData.custom_fields.year"
                                        class="wizard-input" :class="errors.year ? 'has-error' : ''">
                                    <option value="">{{ __('wizard.car.year_placeholder') }}</option>
                                    <template x-for="y in yearOptions" :key="y">
                                        <option :value="y" x-text="y"></option>
                                    </template>
                                </select>
                                <p x-show="errors.year" x-cloak class="text-xs text-red-600 mt-1" x-text="errors.year"></p>
                            </div>

                            {{-- Condition (حالة السيارة) --}}
                            <div>
                                <label class="block text-sm font-semibold text-zinc-700 mb-1.5">{{ __('wizard.car.condition_label') }} <span class="text-red-500">*</span></label>
                                <select x-model="formData.custom_fields.condition"
                                        class="wizard-input" :class="errors.condition ? 'has-error' : ''">
                                    <option value="">{{ __('wizard.car.condition_placeholder') }}</option>
                                    <template x-for="opt in carConditionOptions" :key="opt.value">
                                        <option :value="opt.value" x-text="opt.label"></option>
                                    </template>
                                </select>
                                <p x-show="errors.condition" x-cloak class="text-xs text-red-600 mt-1" x-text="errors.condition"></p>
                            </div>

                            {{-- Mileage (عداد الكيلومترات) --}}
                            <div>
                                <label class="block text-sm font-semibold text-zinc-700 mb-1.5">{{ __('wizard.car.mileage_label') }}</label>
                                <div class="relative">
                                    <input type="number" x-model="formData.custom_fields.mileage" min="0" step="1"
                                           class="wizard-input pe-12" placeholder="{{ __('wizard.car.mileage_placeholder') }}">
                                    <span class="absolute inset-y-0 end-4 flex items-center text-sm text-zinc-400 font-semibold pointer-events-none">{{ __('wizard.car.mileage_unit') }}</span>
                                </div>
                            </div>

                            {{-- Color (لون السيارة) --}}
                            <div>
                                <label class="block text-sm font-semibold text-zinc-700 mb-1.5">{{ __('wizard.car.color_label') }}</label>
                                <input type="text" x-model="formData.custom_fields.color" maxlength="50"
                                       class="wizard-input" placeholder="{{ __('wizard.car.color_placeholder') }}">
                            </div>
                        </div>
                    </div>

                    {{-- ────────────────────────────────────────
                         🏠 مواصفات العقار (قسم العقارات فقط)
                         يطابق RealEstateFields في لوحة الأدمن
                    ──────────────────────────────────────── --}}
                    <div x-show="isRealEstateCategory" x-cloak class="pt-2 border-t border-gray-100">
                        <h3 class="text-sm font-bold text-zinc-700 mb-3 mt-3 flex items-center gap-2">
                            <span class="w-1 h-4 rounded-full inline-block bg-nilex-teal"></span>
                            {{ __('wizard.realestate.section_title') }}
                        </h3>
                        <div class="space-y-4">
                            {{-- Property type (نوع العقار) --}}
                            <div>
                                <label class="block text-sm font-semibold text-zinc-700 mb-1.5">{{ __('wizard.realestate.property_type_label') }} <span class="text-red-500">*</span></label>
                                <select x-model="formData.custom_fields.property_type"
                                        class="wizard-input" :class="errors.property_type ? 'has-error' : ''">
                                    <option value="">{{ __('wizard.realestate.property_type_placeholder') }}</option>
                                    <template x-for="opt in propertyTypeOptions" :key="opt.value">
                                        <option :value="opt.value" x-text="opt.label"></option>
                                    </template>
                                </select>
                                <p x-show="errors.property_type" x-cloak class="text-xs text-red-600 mt-1" x-text="errors.property_type"></p>
                            </div>

                            {{-- Listing type (نوع العرض) --}}
                            <div>
                                <label class="block text-sm font-semibold text-zinc-700 mb-1.5">{{ __('wizard.realestate.listing_type_label') }} <span class="text-red-500">*</span></label>
                                <select x-model="formData.custom_fields.listing_type"
                                        class="wizard-input" :class="errors.listing_type ? 'has-error' : ''">
                                    <option value="">{{ __('wizard.realestate.listing_type_placeholder') }}</option>
                                    <template x-for="opt in listingTypeOptions" :key="opt.value">
                                        <option :value="opt.value" x-text="opt.label"></option>
                                    </template>
                                </select>
                                <p x-show="errors.listing_type" x-cloak class="text-xs text-red-600 mt-1" x-text="errors.listing_type"></p>
                            </div>

                            {{-- Rooms (عدد الغرف) --}}
                            <div>
                                <label class="block text-sm font-semibold text-zinc-700 mb-1.5">{{ __('wizard.realestate.rooms_label') }}</label>
                                <select x-model="formData.custom_fields.rooms" class="wizard-input">
                                    <option value="">{{ __('wizard.realestate.rooms_placeholder') }}</option>
                                    <template x-for="opt in roomsOptions" :key="opt.value">
                                        <option :value="opt.value" x-text="opt.label"></option>
                                    </template>
                                </select>
                            </div>

                            {{-- Bathrooms (عدد الحمامات) --}}
                            <div>
                                <label class="block text-sm font-semibold text-zinc-700 mb-1.5">{{ __('wizard.realestate.bathrooms_label') }}</label>
                                <select x-model="formData.custom_fields.bathrooms" class="wizard-input">
                                    <option value="">{{ __('wizard.realestate.bathrooms_placeholder') }}</option>
                                    <template x-for="opt in bathroomsOptions" :key="opt.value">
                                        <option :value="opt.value" x-text="opt.label"></option>
                                    </template>
                                </select>
                            </div>

                            {{-- Floor (الدور) — قائمة + خيار "أخرى" يفتح نص حر --}}
                            <div>
                                <label class="block text-sm font-semibold text-zinc-700 mb-1.5">{{ __('wizard.realestate.floor_label') }}</label>
                                <select x-model="floorSelection" @change="onFloorChange()" class="wizard-input">
                                    <option value="">{{ __('wizard.realestate.floor_placeholder') }}</option>
                                    <template x-for="opt in floorOptions" :key="opt.value">
                                        <option :value="opt.value" x-text="opt.label"></option>
                                    </template>
                                </select>
                                {{-- نص حر يظهر عند اختيار "أخرى"؛ يُخزَّن مباشرةً في custom_fields.floor --}}
                                <div x-show="floorIsOther" x-cloak class="mt-2">
                                    <label class="block text-sm font-semibold text-zinc-700 mb-1.5">{{ __('wizard.realestate.floor_other_label') }}</label>
                                    <input type="text" x-model="floorOther" @input="onFloorOtherInput()" maxlength="100"
                                           class="wizard-input" placeholder="{{ __('wizard.realestate.floor_other_placeholder') }}">
                                </div>
                            </div>

                            {{-- Finishing (نوع التشطيب) --}}
                            <div>
                                <label class="block text-sm font-semibold text-zinc-700 mb-1.5">{{ __('wizard.realestate.finishing_label') }}</label>
                                <select x-model="formData.custom_fields.finishing" class="wizard-input">
                                    <option value="">{{ __('wizard.realestate.finishing_placeholder') }}</option>
                                    <template x-for="opt in finishingOptions" :key="opt.value">
                                        <option :value="opt.value" x-text="opt.label"></option>
                                    </template>
                                </select>
                            </div>

                            {{-- Area (المساحة) --}}
                            <div>
                                <label class="block text-sm font-semibold text-zinc-700 mb-1.5">{{ __('wizard.realestate.area_label') }}</label>
                                <div class="relative">
                                    <input type="number" x-model="formData.custom_fields.area" min="0" step="1"
                                           class="wizard-input pe-12" placeholder="{{ __('wizard.realestate.area_placeholder') }}">
                                    <span class="absolute inset-y-0 end-4 flex items-center text-sm text-zinc-400 font-semibold pointer-events-none">{{ __('wizard.realestate.area_unit') }}</span>
                                </div>
                            </div>

                            {{-- Compound (هل في كمباوند؟) --}}
                            <div>
                                <label class="block text-sm font-semibold text-zinc-700 mb-1.5">{{ __('wizard.realestate.compound_label') }}</label>
                                <select x-model="formData.custom_fields.compound" class="wizard-input">
                                    <option value="">{{ __('wizard.common.select_placeholder') }}</option>
                                    <template x-for="opt in compoundOptions" :key="opt.value">
                                        <option :value="opt.value" x-text="opt.label"></option>
                                    </template>
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Dynamic custom fields --}}
                    <div x-show="customFieldsSchema.length > 0" x-cloak class="pt-2 border-t border-gray-100">
                        <h3 class="text-sm font-bold text-zinc-700 mb-3 mt-3 flex items-center gap-2">
                            <span class="w-1 h-4 rounded-full inline-block bg-nilex-teal"></span>
                            {{ __('wizard.step2.extra_specs') }}
                        </h3>
                        <div class="space-y-4">
                            <template x-for="field in customFieldsSchema" :key="field.name">
                                <div>
                                    <label class="block text-sm font-semibold text-zinc-700 mb-1.5">
                                        <span x-text="field.label_ar || field.name"></span>
                                        <span x-show="field.required" class="text-red-500">*</span>
                                    </label>

                                    {{-- select --}}
                                    <template x-if="field.type === 'select'">
                                        <select x-model="formData.custom_fields[field.name]"
                                                class="wizard-input" :class="errors['cf_'+field.name] ? 'has-error' : ''">
                                            <option value="">{{ __('wizard.common.select_placeholder') }}</option>
                                            <template x-for="opt in (field.options || [])" :key="opt.value">
                                                <option :value="opt.value" x-text="opt.label"></option>
                                            </template>
                                        </select>
                                    </template>

                                    {{-- textarea --}}
                                    <template x-if="field.type === 'textarea'">
                                        <textarea x-model="formData.custom_fields[field.name]" rows="3"
                                                  class="wizard-input resize-y" :class="errors['cf_'+field.name] ? 'has-error' : ''"></textarea>
                                    </template>

                                    {{-- boolean --}}
                                    <template x-if="field.type === 'boolean'">
                                        <label class="inline-flex items-center gap-2 cursor-pointer">
                                            <input type="checkbox" x-model="formData.custom_fields[field.name]"
                                                   class="w-5 h-5 rounded accent-nilex-teal">
                                            <span class="text-sm text-zinc-600">{{ __('wizard.common.yes') }}</span>
                                        </label>
                                    </template>

                                    {{-- text / number / default --}}
                                    <template x-if="!['select','textarea','boolean'].includes(field.type)">
                                        <input :type="field.type === 'number' ? 'number' : 'text'"
                                               x-model="formData.custom_fields[field.name]"
                                               class="wizard-input" :class="errors['cf_'+field.name] ? 'has-error' : ''">
                                    </template>

                                    <p x-show="errors['cf_'+field.name]" x-cloak class="text-xs text-red-600 mt-1" x-text="errors['cf_'+field.name]"></p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ────────────────────────────────────────
                 STEP 3 — UPLOAD IMAGES
            ──────────────────────────────────────── --}}
            <div x-show="currentStep === 3"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-x-8"
                 x-transition:enter-end="opacity-100 translate-x-0">

                <h2 class="text-lg font-bold text-zinc-900 mb-1">{{ __('wizard.step3.title') }}</h2>
                <p class="text-sm text-zinc-400 mb-5">{{ __('wizard.step3.subtitle') }}</p>

                @if($isEdit)
                {{-- الصور الحالية (كتلة ثابتة الترتيب — حذف فقط؛ الجديدة تُلحَق بعدها) --}}
                <div x-show="existingImages.length > 0" x-cloak class="mb-6">
                    <h3 class="text-sm font-bold text-zinc-700 mb-1 flex items-center gap-2">
                        <span class="w-1 h-4 rounded-full inline-block bg-nilex-teal"></span>
                        {{ __('wizard.edit.existing_images_title') }}
                    </h3>
                    <p class="text-xs text-zinc-400 mb-3">{{ __('wizard.edit.existing_images_hint') }}</p>
                    <div class="grid grid-cols-3 md:grid-cols-4 gap-3">
                        <template x-for="(img, idx) in existingImages" :key="img.id">
                            <div class="relative group rounded-xl overflow-hidden border border-gray-200 aspect-square bg-gray-50">
                                <img :src="img.url" class="w-full h-full object-cover" alt="">
                                <button type="button" @click="removeExistingImage(idx)"
                                        :title="'{{ __('wizard.edit.img_delete_existing') }}'"
                                        class="absolute top-1.5 end-1.5 w-7 h-7 rounded-full bg-black/60 hover:bg-red-600 text-white flex items-center justify-center transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                                <span class="absolute bottom-1.5 start-1.5 text-[9px] font-bold px-1.5 py-0.5 rounded-full bg-zinc-700 text-white">
                                    {{ __('wizard.edit.img_existing_badge') }}
                                </span>
                            </div>
                        </template>
                    </div>
                </div>
                @endif

                {{-- Drop zone --}}
                <div class="drop-zone border-2 border-dashed border-gray-300 rounded-2xl min-h-40 flex flex-col items-center justify-center text-center p-6 cursor-pointer transition-colors"
                     :class="{ 'dragging': isDragging }"
                     @click="$refs.fileInput.click()"
                     @dragover.prevent="isDragging = true"
                     @dragleave.prevent="isDragging = false"
                     @drop.prevent="isDragging = false; addImages($event.dataTransfer.files)">
                    <div class="w-14 h-14 rounded-2xl bg-green-50 flex items-center justify-center mb-3">
                        <svg class="w-7 h-7 text-nilex-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                    </div>
                    <p class="text-sm font-bold text-zinc-700">{{ __('wizard.step3.drop_text') }}</p>
                    <p class="text-xs text-zinc-400 mt-1">{{ __('wizard.step3.formats_hint') }}</p>
                    <input type="file" x-ref="fileInput" class="hidden" multiple
                           accept="image/jpeg,image/png,image/webp"
                           @change="addImages($event.target.files); $event.target.value=''">
                </div>

                {{-- Soft warning (not error) — يأخذ الصور الحالية في الاعتبار بوضع التعديل --}}
                <div x-show="images.length === 0 && existingImages.length === 0" x-cloak
                     class="mt-4 flex items-start gap-2 bg-amber-50 border border-amber-200 text-amber-700 text-xs rounded-xl px-4 py-3">
                    <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                    <span>{{ __('wizard.step3.warning_no_image') }}</span>
                </div>

                {{-- Image count --}}
                <p x-show="images.length > 0" x-cloak class="mt-4 text-xs font-semibold text-zinc-500">
                    <span x-text="images.length"></span> / 10 {{ __('wizard.step3.count_suffix') }}
                </p>

                {{-- Previews --}}
                <div x-show="imagePreviews.length > 0" x-cloak class="grid grid-cols-3 md:grid-cols-4 gap-3 mt-3">
                    <template x-for="(preview, index) in imagePreviews" :key="preview.url">
                        <div class="relative group rounded-xl overflow-hidden border border-gray-200 aspect-square bg-gray-50">
                            <img :src="preview.url" class="w-full h-full object-cover" :alt="preview.name">
                            <button type="button" @click="removeImage(index)"
                                    class="absolute top-1.5 end-1.5 w-7 h-7 rounded-full bg-black/60 hover:bg-red-600 text-white flex items-center justify-center transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                            <span x-show="index === 0"
                                  class="absolute bottom-1.5 start-1.5 text-[9px] font-bold px-1.5 py-0.5 rounded-full bg-nilex-teal text-white">
                                {{ __('wizard.step3.main_badge') }}
                            </span>
                        </div>
                    </template>
                </div>
            </div>

            {{-- ────────────────────────────────────────
                 STEP 4 — CONTACT & REVIEW
            ──────────────────────────────────────── --}}
            <div x-show="currentStep === 4"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-x-8"
                 x-transition:enter-end="opacity-100 translate-x-0">

                <h2 class="text-lg font-bold text-zinc-900 mb-1">{{ __('wizard.step4.title') }}</h2>
                <p class="text-sm text-zinc-400 mb-5">{{ __('wizard.step4.subtitle') }}</p>

                {{-- Contact fields --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-semibold text-zinc-700 mb-1.5">{{ __('wizard.step4.phone_label') }} <span class="text-red-500">*</span></label>
                        <input type="text" x-model="formData.phone" inputmode="tel"
                               class="wizard-input" :class="errors.phone ? 'has-error' : ''"
                               placeholder="01xxxxxxxxx">
                        <p x-show="errors.phone" x-cloak class="text-xs text-red-600 mt-1" x-text="errors.phone"></p>
                        @if(! $isPhoneVerified)
                        <p class="mt-1.5 flex items-center gap-1 text-xs text-nilex-teal font-semibold">
                            <span>💡</span> {{ __('wizard.step4.verify_phone_hint') }}
                        </p>
                        @endif
                    </div>

                    {{-- Governorate (level 0) --}}
                    <div>
                        <label class="block text-sm font-semibold text-zinc-700 mb-1.5">{{ __('wizard.step4.governorate_label') }}</label>
                        <select x-model="formData.governorate_id" @change="onGovernorateChange()"
                                class="wizard-input">
                            <option value="">{{ __('wizard.step4.governorate_placeholder') }}</option>
                            <template x-for="gov in governorates" :key="gov.id">
                                <option :value="gov.id" x-text="gov.name_ar"></option>
                            </template>
                        </select>
                    </div>

                    {{-- City (level 1, child of selected governorate) --}}
                    <div>
                        <label class="block text-sm font-semibold text-zinc-700 mb-1.5">{{ __('wizard.step4.city_label') }}</label>
                        <select x-model="formData.location_id"
                                :disabled="!formData.governorate_id"
                                class="wizard-input disabled:bg-gray-50 disabled:text-zinc-400 disabled:cursor-not-allowed"
                                :class="errors.location_id ? 'has-error' : ''">
                            <option value="" x-text="formData.governorate_id ? '{{ __('wizard.step4.city_placeholder') }}' : '{{ __('wizard.step4.city_placeholder_no_gov') }}'"></option>
                            <template x-for="city in cities" :key="city.id">
                                <option :value="city.id" x-text="city.name_ar"></option>
                            </template>
                        </select>
                        <p x-show="errors.location_id" x-cloak class="text-xs text-red-600 mt-1" x-text="errors.location_id"></p>
                    </div>
                </div>

                {{-- Completion checklist --}}
                <div class="bg-green-50 border border-green-100 rounded-2xl p-4 mb-6">
                    <h3 class="text-sm font-bold text-zinc-800 mb-3">{{ __('wizard.step4.checklist_title') }}</h3>
                    <ul class="space-y-2 text-sm">
                        <template x-for="item in checklist" :key="item.label">
                            <li class="flex items-center gap-2">
                                <span class="w-5 h-5 rounded-full flex items-center justify-center shrink-0"
                                      :class="item.done ? 'bg-nilex-teal text-white' : 'bg-gray-200 text-gray-400'">
                                    <svg x-show="item.done" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </span>
                                <span :class="item.done ? 'text-zinc-700' : 'text-zinc-400'" x-text="item.label"></span>
                            </li>
                        </template>
                    </ul>
                </div>

                {{-- Summary cards --}}
                <div class="space-y-3">
                    {{-- Category --}}
                    <div class="border border-gray-100 rounded-2xl p-4 flex items-start justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-bold text-zinc-400 mb-1">{{ __('wizard.step4.summary_category') }}</p>
                            <p class="text-sm font-semibold text-zinc-800" x-text="activeCategory ? activeCategory.name : '—'"></p>
                        </div>
                        <button type="button" @click="goToStep(1)" class="text-xs font-bold text-nilex-teal hover:underline shrink-0">{{ __('wizard.common.edit') }}</button>
                    </div>

                    {{-- Details --}}
                    <div class="border border-gray-100 rounded-2xl p-4">
                        <div class="flex items-start justify-between gap-3 mb-2">
                            <p class="text-[11px] font-bold text-zinc-400">{{ __('wizard.step4.summary_details') }}</p>
                            <button type="button" @click="goToStep(2)" class="text-xs font-bold text-nilex-teal hover:underline shrink-0">{{ __('wizard.common.edit') }}</button>
                        </div>
                        <p class="text-sm font-bold text-zinc-800" x-text="formData.title || '—'"></p>
                        <p class="text-xs text-zinc-500 mt-1 line-clamp-2" x-text="formData.description || ''"></p>
                        <div class="flex flex-wrap gap-2 mt-3">
                            <span class="text-[11px] font-semibold bg-gray-50 text-zinc-600 px-2.5 py-1 rounded-lg" x-text="conditionLabel()"></span>
                            <span class="text-[11px] font-semibold bg-gray-50 text-zinc-600 px-2.5 py-1 rounded-lg" x-text="priceTypeLabel()"></span>
                            <span class="text-[11px] font-bold bg-green-50 text-nilex-teal px-2.5 py-1 rounded-lg">
                                <span x-text="formData.price ? Number(formData.price).toLocaleString('en-US') : '0'"></span> {{ __('wizard.common.currency') }}
                            </span>
                        </div>
                    </div>

                    {{-- Images --}}
                    <div class="border border-gray-100 rounded-2xl p-4">
                        <div class="flex items-start justify-between gap-3 mb-2">
                            <p class="text-[11px] font-bold text-zinc-400">{{ __('wizard.step4.summary_images') }} (<span x-text="images.length + existingImages.length"></span>)</p>
                            <button type="button" @click="goToStep(3)" class="text-xs font-bold text-nilex-teal hover:underline shrink-0">{{ __('wizard.common.edit') }}</button>
                        </div>
                        <div x-show="imagePreviews.length > 0 || existingImages.length > 0" class="flex gap-2 flex-wrap">
                            <template x-for="img in existingImages.slice(0,5)" :key="'ex-' + img.id">
                                <img :src="img.url" class="w-12 h-12 rounded-lg object-cover border border-gray-200">
                            </template>
                            <template x-for="preview in imagePreviews.slice(0,5)" :key="preview.url">
                                <img :src="preview.url" class="w-12 h-12 rounded-lg object-cover border border-gray-200">
                            </template>
                        </div>
                        <p x-show="imagePreviews.length === 0 && existingImages.length === 0" x-cloak class="text-xs text-zinc-400">{{ __('wizard.step4.no_images') }}</p>
                    </div>
                </div>

                {{-- ── ⭐ تمييز الإعلان بالنقاط — مستثنى تماماً من وضع التعديل ── --}}
                @unless($isEdit)
                <div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 p-4">
                    <div class="flex items-center justify-between gap-2 mb-1">
                        <h3 class="text-sm font-bold text-zinc-800 flex items-center gap-1.5">
                            <span>⭐</span> {{ __('wizard.feature.title') }}
                        </h3>
                        <span class="text-xs font-semibold text-zinc-500">
                            {{ __('wizard.feature.current_points') }}
                            <span class="text-nilex-teal font-bold" x-text="userPoints"></span>
                            {{ __('wizard.common.points_unit') }}
                        </span>
                    </div>
                    <p class="text-xs text-zinc-500 mb-3">{{ __('wizard.feature.desc') }}</p>
                    <select x-model.number="formData.feature_days" class="wizard-input">
                        <template x-for="opt in featureOptions" :key="opt.days">
                            <option
                                :value="opt.days"
                                :disabled="opt.cost > 0 && userPoints < opt.cost"
                                x-text="opt.cost === 0
                                    ? opt.label
                                    : (userPoints >= opt.cost
                                        ? opt.label + ' — ' + opt.cost + ' {{ __('wizard.common.points_unit') }}'
                                        : opt.label + ' — ' + opt.cost + ' {{ __('wizard.common.points_unit') }}  {{ __('wizard.feature.insufficient') }}')">
                            </option>
                        </template>
                    </select>
                    <p x-show="formData.feature_days > 0" x-cloak
                       class="mt-2 text-xs text-amber-700 font-semibold">
                        {{ __('wizard.feature.deduct_prefix') }}
                        <span x-text="NILEX_FEATURE_COSTS[formData.feature_days] ?? 0"></span>
                        {{ __('wizard.feature.deduct_suffix') }}
                    </p>
                </div>
                @endunless
            </div>

            {{-- ══════════════════════════════════════════
                 NAVIGATION BUTTONS
            ══════════════════════════════════════════ --}}
            <div class="flex items-center justify-between gap-3 mt-8 pt-5 border-t border-gray-100">
                {{-- Back --}}
                <button type="button" @click="prevStep()" x-show="currentStep > 1" x-cloak
                        class="inline-flex items-center gap-1.5 font-semibold text-zinc-600 border border-gray-200 hover:border-gray-400 px-5 py-2.5 rounded-xl text-sm min-h-[44px]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                    </svg>
                    {{ __('wizard.common.prev') }}
                </button>
                <div x-show="currentStep === 1"></div>

                {{-- Next --}}
                <button type="button" @click="nextStep()" x-show="currentStep < totalSteps"
                        class="btn-nilex-primary inline-flex items-center gap-1.5 px-6 py-2.5 rounded-xl text-sm ms-auto min-h-[44px]">
                    {{ __('wizard.common.next') }}
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>

                {{-- Submit --}}
                <div x-show="currentStep === totalSteps" x-cloak class="ms-auto flex flex-col items-end gap-1.5">
                    <button type="submit" :disabled="isSubmitting"
                            class="btn-nilex-primary inline-flex items-center gap-2 px-7 py-2.5 rounded-xl text-sm min-h-[44px]">
                        <svg x-show="isSubmitting" x-cloak class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span x-text="isSubmitting ? '{{ $isEdit ? __('wizard.edit.submitting') : __('wizard.step4.submitting') }}' : '{{ $isEdit ? __('wizard.edit.submit_btn') : __('wizard.step4.submit_btn') }}'"></span>
                    </button>
                    <p class="text-[11px] text-zinc-400 flex items-center gap-1">
                        <span>💡</span> {{ $isEdit ? __('wizard.edit.submit_hint') : __('wizard.step4.submit_hint') }}
                    </p>
                </div>
            </div>
        </form>

        {{-- Auto-save hint --}}
        <p class="text-center text-[11px] text-zinc-400 mt-4">
            <svg class="w-3.5 h-3.5 inline-block -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            {{ __('wizard.common.autosave') }}
        </p>
    </div>
</div>
@endsection

@push('scripts')
@php
    $categories->each(function ($category) {
        $category->setAttribute('icon_url', $category->getFirstMediaUrl('icon') ?: null);
        $category->children->each(function ($child) {
            $child->setAttribute('icon_url', $child->getFirstMediaUrl('icon') ?: null);
        });
    });
@endphp
<script>
    const NILEX_CATEGORIES    = @json($categories);
    const NILEX_LOCATIONS     = @json($governorates);
    const NILEX_CAR_BRANDS    = @json($carBrands);
    // Wizard UI translations (B.3a). Consumed by the Alpine component in B.3b/B.3c.
    const NILEX_WIZARD_I18N   = @json(__('wizard'));
    const NILEX_STORE_URL     = "{{ route('listings.store') }}";
    const NILEX_AI_URL        = "{{ route('listings.ai-generate') }}";
    const NILEX_DASHBOARD_URL = "{{ route('dashboard') }}";
    const NILEX_PREFILL_PHONE        = @json(optional(auth()->user())->phone ?? '');
    // Pre-fill location from user profile (set in HomeController::create/edit).
    const NILEX_PREFILL_LOCATION_ID    = @json($prefillLocationId ?? '');
    const NILEX_PREFILL_GOVERNORATE_ID = @json($prefillGovernorateId ?? '');
    // مفتاح المسودة منفصل لكل إعلان في وضع التعديل (وموحّد في وضع الإنشاء).
    const NILEX_WIZARD_KEY    = @json($isEdit ? ('nilex_listing_wizard_edit_' . $listing->id) : 'nilex_listing_wizard');
    const NILEX_PHONE_VERIFIED = @json($isPhoneVerified);
    const NILEX_USER_POINTS    = @json($userPoints);
    const NILEX_FEATURE_COSTS  = @json(\App\Models\Listing::FEATURE_COSTS);
    // Mirrors the listings.price column cap (decimal(12,2)) so the user gets an
    // instant client-side error before submitting, matching the server rule.
    const NILEX_MAX_PRICE      = 9999999999.99;

    // ── وضع التعديل (Edit) — قيم محايدة في وضع الإنشاء ──
    const NILEX_WIZARD_MODE   = @json($mode);
    const NILEX_UPDATE_URL    = @json($isEdit ? route('listings.update', $listing) : null);
    const NILEX_EDIT_DATA     = @json($editData);   // DTO معبّأ (null في الإنشاء)
    const NILEX_EDIT_IMAGES   = @json($editImages); // [{id,url}] (مصفوفة فارغة في الإنشاء)
    const NILEX_EDIT_ROOT_ID  = @json($editRootId); // جذر القسم لاشتقاق القسم النشط

    function listingWizard() {
        return {
            currentStep: 1,
            totalSteps: 4,
            categories: NILEX_CATEGORIES,
            governorates: NILEX_LOCATIONS,
            carBrands: NILEX_CAR_BRANDS,
            selectedRootId: null,

            formData: {
                category_id: '',
                title: '',
                description: '',
                condition: 'new',
                price: '',
                price_type: 'fixed',
                custom_fields: {},
                car_brand_id: '',
                car_model_id: '',
                phone: '',
                governorate_id: '',
                location_id: '',
                feature_days: 0,
            },

            // 🚗 خيارات الوقود وناقل الحركة — يجب أن تطابق لوحة الأدمن (CarFields)
            // القيم محايدة (مفاتيح إنجليزية) — الـ label فقط يُترجم من NILEX_WIZARD_I18N (B.3b)
            fuelOptions: [
                { value: 'petrol',   label: NILEX_WIZARD_I18N.options.fuel.petrol },
                { value: 'diesel',   label: NILEX_WIZARD_I18N.options.fuel.diesel },
                { value: 'electric', label: NILEX_WIZARD_I18N.options.fuel.electric },
                { value: 'hybrid',   label: NILEX_WIZARD_I18N.options.fuel.hybrid },
                { value: 'gas',      label: NILEX_WIZARD_I18N.options.fuel.gas },
            ],
            transmissionOptions: [
                { value: 'automatic', label: NILEX_WIZARD_I18N.options.transmission.automatic },
                { value: 'manual',    label: NILEX_WIZARD_I18N.options.transmission.manual },
            ],
            // 🚗 حالة السيارة — قائمة منسدلة. القيمة تبقى نصاً عربياً ثابتاً (تُخزَّن في
            // custom_fields.condition ويعتمد عليها show.blade.php) — الـ label فقط يُترجم.
            carConditionOptions: [
                { value: 'فابريكا (لم تُدهن)', label: NILEX_WIZARD_I18N.options.condition.fabrica },
                { value: 'حالة ممتازة',        label: NILEX_WIZARD_I18N.options.condition.excellent },
                { value: 'حالة جيدة',          label: NILEX_WIZARD_I18N.options.condition.good },
                { value: 'حالة مقبولة',        label: NILEX_WIZARD_I18N.options.condition.fair },
                { value: 'تحتاج صيانة',        label: NILEX_WIZARD_I18N.options.condition.needs_maintenance },
            ],

            // 🏠 خيارات العقار — يجب أن تطابق لوحة الأدمن (RealEstateFields)
            propertyTypeOptions: [
                { value: 'apartment', label: NILEX_WIZARD_I18N.options.property_type.apartment },
                { value: 'villa',     label: NILEX_WIZARD_I18N.options.property_type.villa },
                { value: 'duplex',    label: NILEX_WIZARD_I18N.options.property_type.duplex },
                { value: 'studio',    label: NILEX_WIZARD_I18N.options.property_type.studio },
                { value: 'chalet',    label: NILEX_WIZARD_I18N.options.property_type.chalet },
                { value: 'office',    label: NILEX_WIZARD_I18N.options.property_type.office },
                { value: 'shop',      label: NILEX_WIZARD_I18N.options.property_type.shop },
                { value: 'warehouse', label: NILEX_WIZARD_I18N.options.property_type.warehouse },
                { value: 'land',      label: NILEX_WIZARD_I18N.options.property_type.land },
                { value: 'building',  label: NILEX_WIZARD_I18N.options.property_type.building },
            ],
            listingTypeOptions: [
                { value: 'sale', label: NILEX_WIZARD_I18N.options.listing_type.sale },
                { value: 'rent', label: NILEX_WIZARD_I18N.options.listing_type.rent },
            ],
            roomsOptions: [
                { value: '1',  label: NILEX_WIZARD_I18N.options.rooms['1'] },
                { value: '2',  label: NILEX_WIZARD_I18N.options.rooms['2'] },
                { value: '3',  label: NILEX_WIZARD_I18N.options.rooms['3'] },
                { value: '4',  label: NILEX_WIZARD_I18N.options.rooms['4'] },
                { value: '5',  label: NILEX_WIZARD_I18N.options.rooms['5'] },
                { value: '6+', label: NILEX_WIZARD_I18N.options.rooms['6+'] },
            ],
            bathroomsOptions: [
                { value: '1',  label: NILEX_WIZARD_I18N.options.bathrooms['1'] },
                { value: '2',  label: NILEX_WIZARD_I18N.options.bathrooms['2'] },
                { value: '3',  label: NILEX_WIZARD_I18N.options.bathrooms['3'] },
                { value: '4+', label: NILEX_WIZARD_I18N.options.bathrooms['4+'] },
            ],
            floorOptions: [
                { value: 'ground',  label: NILEX_WIZARD_I18N.options.floor.ground },
                { value: '1',       label: NILEX_WIZARD_I18N.options.floor['1'] },
                { value: '2',       label: NILEX_WIZARD_I18N.options.floor['2'] },
                { value: '3',       label: NILEX_WIZARD_I18N.options.floor['3'] },
                { value: '4',       label: NILEX_WIZARD_I18N.options.floor['4'] },
                { value: '5',       label: NILEX_WIZARD_I18N.options.floor['5'] },
                { value: '6+',      label: NILEX_WIZARD_I18N.options.floor['6+'] },
                { value: 'rooftop', label: NILEX_WIZARD_I18N.options.floor.rooftop },
                { value: 'other',   label: NILEX_WIZARD_I18N.options.floor.other },
            ],
            finishingOptions: [
                { value: 'super_lux',  label: NILEX_WIZARD_I18N.options.finishing.super_lux },
                { value: 'lux',        label: NILEX_WIZARD_I18N.options.finishing.lux },
                { value: 'semi_lux',   label: NILEX_WIZARD_I18N.options.finishing.semi_lux },
                { value: 'core_shell', label: NILEX_WIZARD_I18N.options.finishing.core_shell },
                { value: 'unfinished', label: NILEX_WIZARD_I18N.options.finishing.unfinished },
                { value: 'furnished',  label: NILEX_WIZARD_I18N.options.finishing.furnished },
            ],
            compoundOptions: [
                { value: 'yes', label: NILEX_WIZARD_I18N.options.compound.yes },
                { value: 'no',  label: NILEX_WIZARD_I18N.options.compound.no },
            ],

            errors: {},
            // 🏠 حقل الدور: قيمة القائمة المنسدلة + النص الحر عند اختيار "أخرى".
            // متغيّران مساعدان (خارج formData) — القيمة النهائية تُكتب في
            // formData.custom_fields.floor مباشرةً (نصاً حراً أو قيمة مرمّزة).
            floorSelection: '',
            floorOther: '',
            images: [],
            imagePreviews: [],
            // وضع التعديل: الصور الحالية (كتلة ثابتة) + معرّفات المحذوفة منها.
            existingImages: Array.isArray(NILEX_EDIT_IMAGES) ? NILEX_EDIT_IMAGES.map(i => ({ ...i })) : [],
            removedImageIds: [],
            isSubmitting: false,
            submitError: '',
            isDragging: false,

            // ⭐ خيارات التمييز — التكلفة من Listing::FEATURE_COSTS (مصدر واحد)
            featureOptions: [
                { days: 0, label: NILEX_WIZARD_I18N.feature.opt_0, cost: 0 },
                ...Object.entries(NILEX_FEATURE_COSTS).map(([days, cost]) => ({
                    days: Number(days),
                    label: NILEX_WIZARD_I18N.feature['opt_' + days],
                    cost: Number(cost),
                })),
            ],
            userPoints: NILEX_USER_POINTS,

            // 🤖 المساعد الذكي (Gemini)
            aiPrompt: '',
            aiLoading: false,
            aiMessage: null,

            priceTypes: [
                { value: 'fixed',      label: NILEX_WIZARD_I18N.options.price_type.fixed },
                { value: 'negotiable', label: NILEX_WIZARD_I18N.options.price_type.negotiable },
                { value: 'on_contact', label: NILEX_WIZARD_I18N.options.price_type.on_contact },
            ],

            init() {
                // وضع التعديل: نعبّئ من بيانات الخادم أولاً، ثم تُتيح المسودة (per-id)
                // الكتابة فوقها لو كان المستخدم في منتصف تعديل غير محفوظ.
                if (NILEX_WIZARD_MODE === 'edit' && NILEX_EDIT_DATA) {
                    this.applyEditData();
                }
                this.loadFromLocalStorage();
                // اشتقاق حالة قائمة الدور بعد تعبئة formData (تعديل + مسودة محلية).
                this.syncFloorFromFormData();
                if (!this.formData.phone) {
                    this.formData.phone = NILEX_PREFILL_PHONE || '';
                }
                // Pre-fill governorate + city from the user's saved profile location
                // (only in create mode; edit mode has the listing's own location).
                if (NILEX_WIZARD_MODE === 'create') {
                    if (!this.formData.governorate_id && NILEX_PREFILL_GOVERNORATE_ID) {
                        this.formData.governorate_id = NILEX_PREFILL_GOVERNORATE_ID;
                    }
                    if (!this.formData.location_id && NILEX_PREFILL_LOCATION_ID) {
                        this.formData.location_id = NILEX_PREFILL_LOCATION_ID;
                    }
                }
                this.$watch('formData', () => this.saveToLocalStorage(), { deep: true });
                this.$watch('selectedRootId', () => this.saveToLocalStorage());
            },

            // تعبئة formData من DTO الخادم (تحويل بيانات الإعلان → شكل الويزارد).
            applyEditData() {
                const d = NILEX_EDIT_DATA;
                this.formData.category_id  = d.category_id ?? '';
                this.formData.title        = d.title ?? '';
                this.formData.description  = d.description ?? '';
                this.formData.condition    = d.condition ?? 'new';
                this.formData.price        = d.price ?? '';
                this.formData.price_type   = d.price_type ?? 'fixed';
                this.formData.custom_fields = (d.custom_fields && typeof d.custom_fields === 'object' && !Array.isArray(d.custom_fields))
                    ? { ...d.custom_fields } : {};
                this.formData.car_brand_id = d.car_brand_id ?? '';
                this.formData.car_model_id = d.car_model_id ?? '';
                this.formData.phone        = d.phone ?? '';
                this.formData.governorate_id = d.governorate_id ?? '';
                this.formData.location_id  = d.location_id ?? '';
                this.formData.feature_days = 0; // التمييز مستثنى من التعديل
                this.selectedRootId        = NILEX_EDIT_ROOT_ID;
            },

            // ── Derived state ──────────────────────────────────────────
            get selectedRoot() {
                return this.categories.find(c => c.id === this.selectedRootId) || null;
            },
            get subCategories() {
                return (this.selectedRoot && this.selectedRoot.children) ? this.selectedRoot.children : [];
            },
            get activeCategory() {
                const id = this.formData.category_id;
                if (!id) return null;
                for (const root of this.categories) {
                    if (root.id == id) return root;
                    for (const child of (root.children || [])) {
                        if (child.id == id) return child;
                    }
                }
                return null;
            },
            get customFieldsSchema() {
                const cat = this.activeCategory;
                return (cat && Array.isArray(cat.custom_fields_schema)) ? cat.custom_fields_schema : [];
            },
            // 🚗 قسم السيارات — يعتمد على slug القسم النشط
            get isCarCategory() {
                const cat = this.activeCategory;
                return !!(cat && cat.slug === 'cars');
            },
            // 🏠 قسم العقارات — يعتمد على slug القسم النشط
            get isRealEstateCategory() {
                const cat = this.activeCategory;
                return !!(cat && cat.slug === 'real-estate');
            },
            // 🏠 هل اختار المستخدم "أخرى" في قائمة الدور؟ (يُظهر حقل النص الحر)
            get floorIsOther() {
                return this.floorSelection === 'other';
            },
            // سنوات الصنع — من السنة الحالية وحتى 1970 (يطابق لوحة الأدمن)
            get yearOptions() {
                const years = [];
                for (let y = new Date().getFullYear(); y >= 1970; y--) {
                    years.push(String(y));
                }
                return years;
            },
            // Models of the currently selected brand (dependent dropdown).
            get carModels() {
                const brand = this.carBrands.find(b => b.id == this.formData.car_brand_id);
                return (brand && Array.isArray(brand.models)) ? brand.models : [];
            },
            // Whether the selected brand is the "أخرى/Other" entry (slug === 'other').
            get selectedBrandIsOther() {
                const brand = this.carBrands.find(b => b.id == this.formData.car_brand_id);
                return !!(brand && brand.slug === 'other');
            },
            // Child cities of the currently selected governorate (dependent dropdown).
            get cities() {
                const gov = this.governorates.find(g => g.id == this.formData.governorate_id);
                return (gov && Array.isArray(gov.children)) ? gov.children : [];
            },
            get checklist() {
                return [
                    { label: NILEX_WIZARD_I18N.checklist.category,    done: !!this.formData.category_id },
                    { label: NILEX_WIZARD_I18N.checklist.title,       done: this.formData.title.trim().length > 0 },
                    { label: NILEX_WIZARD_I18N.checklist.description, done: this.formData.description.trim().length >= 20 },
                    { label: NILEX_WIZARD_I18N.checklist.price,       done: this.formData.price !== '' && Number(this.formData.price) >= 0 },
                    { label: NILEX_WIZARD_I18N.checklist.images,      done: this.images.length > 0 || this.existingImages.length > 0 },
                    { label: NILEX_WIZARD_I18N.checklist.phone,       done: this.formData.phone.trim().length > 0 },
                ];
            },

            // ── Category selection ─────────────────────────────────────
            selectRoot(cat) {
                this.selectedRootId = cat.id;
                this.formData.category_id = cat.id;
                this.formData.custom_fields = {};
                this.resetCarFields();
                this.resetFloorFields();
                delete this.errors.category_id;
            },
            selectSub(sub) {
                this.formData.category_id = sub.id;
                this.formData.custom_fields = {};
                this.resetCarFields();
                this.resetFloorFields();
                delete this.errors.category_id;
            },
            // ── Car selection ──────────────────────────────────────────
            // Reset the chosen model (and manual brand text) whenever the brand
            // changes so the stored model always belongs to the selected brand.
            onCarBrandChange() {
                this.formData.car_model_id = '';
                if (this.formData.custom_fields) {
                    delete this.formData.custom_fields.car_brand_other;
                }
                delete this.errors.car_model_id;
                delete this.errors.car_brand_other;
            },
            resetCarFields() {
                this.formData.car_brand_id = '';
                this.formData.car_model_id = '';
            },
            // إعادة ضبط حالة حقل الدور عند تغيير القسم (custom_fields صُفِّرت).
            resetFloorFields() {
                this.floorSelection = '';
                this.floorOther = '';
            },

            // ── Location selection ─────────────────────────────────────
            // Reset the chosen city whenever the governorate changes so the
            // stored location_id always belongs to the selected governorate.
            onGovernorateChange() {
                this.formData.location_id = '';
                delete this.errors.location_id;
            },

            // ── 🏠 Floor (الدور) ───────────────────────────────────────
            // تغيير القائمة: لو "أخرى" نكتب النص الحر الحالي (قد يكون فارغاً)،
            // وإلا نكتب القيمة المرمّزة مباشرةً في custom_fields.floor.
            onFloorChange() {
                if (this.floorSelection === 'other') {
                    this.formData.custom_fields.floor = this.floorOther || '';
                } else {
                    this.floorOther = '';
                    this.formData.custom_fields.floor = this.floorSelection;
                }
            },
            // كتابة النص الحر: تُخزَّن القيمة كما هي (لا نخزّن 'other' إطلاقاً).
            onFloorOtherInput() {
                this.formData.custom_fields.floor = this.floorOther;
            },
            // اشتقاق حالة القائمة/النص من القيمة المخزّنة (مسودة محلية أو تعديل).
            // قيمة غير معروفة في floorOptions ⇒ نص حر ⇒ وضع "أخرى".
            syncFloorFromFormData() {
                const v = this.formData.custom_fields ? this.formData.custom_fields.floor : '';
                if (v === undefined || v === null || v === '') {
                    this.floorSelection = '';
                    this.floorOther = '';
                    return;
                }
                const isKnown = this.floorOptions.some(o => o.value !== 'other' && o.value === v);
                if (isKnown) {
                    this.floorSelection = v;
                    this.floorOther = '';
                } else {
                    this.floorSelection = 'other';
                    this.floorOther = v;
                }
            },

            // ── Navigation ─────────────────────────────────────────────
            nextStep() {
                if (!this.validateStep(this.currentStep)) {
                    this.scrollTop();
                    return;
                }
                if (this.currentStep < this.totalSteps) this.currentStep++;
                this.scrollTop();
            },
            prevStep() {
                if (this.currentStep > 1) this.currentStep--;
                this.scrollTop();
            },
            goToStep(step) {
                this.currentStep = step;
                this.scrollTop();
            },
            scrollTop() {
                this.$el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            },

            // ── Validation ─────────────────────────────────────────────
            validateStep(step) {
                this.errors = {};
                this.submitError = '';
                let ok = true;

                if (step === 1) {
                    if (!this.formData.category_id) {
                        this.errors.category_id = NILEX_WIZARD_I18N.validation.category_required;
                        ok = false;
                    } else if (this.selectedRoot && this.subCategories.length > 0
                               && this.formData.category_id == this.selectedRoot.id) {
                        this.errors.category_id = NILEX_WIZARD_I18N.validation.subcategory_required;
                        ok = false;
                    }
                }

                if (step === 2) {
                    if (!this.formData.title.trim()) {
                        this.errors.title = NILEX_WIZARD_I18N.validation.title_required;
                        ok = false;
                    } else if (this.formData.title.length > 255) {
                        this.errors.title = NILEX_WIZARD_I18N.validation.title_max;
                        ok = false;
                    }
                    if (this.formData.description.trim().length < 20) {
                        this.errors.description = NILEX_WIZARD_I18N.validation.desc_min;
                        ok = false;
                    }
                    if (this.formData.price === '' || isNaN(this.formData.price) || Number(this.formData.price) < 0) {
                        this.errors.price = NILEX_WIZARD_I18N.validation.price_invalid;
                        ok = false;
                    } else if (Number(this.formData.price) > NILEX_MAX_PRICE) {
                        this.errors.price = NILEX_WIZARD_I18N.validation.price_too_high;
                        ok = false;
                    }
                    for (const field of this.customFieldsSchema) {
                        if (field.required) {
                            const val = this.formData.custom_fields[field.name];
                            if (val === undefined || val === '' || val === null || val === false) {
                                this.errors['cf_' + field.name] = NILEX_WIZARD_I18N.validation.field_required
                                    .replace(':field', field.label_ar || field.name);
                                ok = false;
                            }
                        }
                    }

                    // 🚗 حقول السيارة المطلوبة (قسم السيارات فقط)
                    if (this.isCarCategory) {
                        if (!this.formData.car_brand_id) {
                            this.errors.car_brand_id = NILEX_WIZARD_I18N.validation.car_brand_required;
                            ok = false;
                        }
                        if (this.selectedBrandIsOther && !(this.formData.custom_fields.car_brand_other || '').trim()) {
                            this.errors.car_brand_other = NILEX_WIZARD_I18N.validation.car_brand_other_required;
                            ok = false;
                        }
                        if (!this.formData.car_model_id) {
                            this.errors.car_model_id = NILEX_WIZARD_I18N.validation.car_model_required;
                            ok = false;
                        }
                        if (!this.formData.custom_fields.fuel) {
                            this.errors.fuel = NILEX_WIZARD_I18N.validation.fuel_required;
                            ok = false;
                        }
                        if (!this.formData.custom_fields.transmission) {
                            this.errors.transmission = NILEX_WIZARD_I18N.validation.transmission_required;
                            ok = false;
                        }
                        if (!this.formData.custom_fields.year) {
                            this.errors.year = NILEX_WIZARD_I18N.validation.year_required;
                            ok = false;
                        }
                        if (!this.formData.custom_fields.condition) {
                            this.errors.condition = NILEX_WIZARD_I18N.validation.condition_required;
                            ok = false;
                        }
                    }

                    // 🏠 حقول العقار المطلوبة (قسم العقارات فقط)
                    if (this.isRealEstateCategory) {
                        if (!this.formData.custom_fields.property_type) {
                            this.errors.property_type = NILEX_WIZARD_I18N.validation.property_type_required;
                            ok = false;
                        }
                        if (!this.formData.custom_fields.listing_type) {
                            this.errors.listing_type = NILEX_WIZARD_I18N.validation.listing_type_required;
                            ok = false;
                        }
                    }
                }

                if (step === 4) {
                    if (!this.formData.phone.trim()) {
                        this.errors.phone = NILEX_WIZARD_I18N.validation.phone_required;
                        ok = false;
                    }
                }

                return ok;
            },

            // ── Images ─────────────────────────────────────────────────
            addImages(fileList) {
                this.submitError = '';
                const allowed = ['image/jpeg', 'image/png', 'image/webp'];
                for (const file of Array.from(fileList)) {
                    if (this.images.length >= 10) {
                        this.submitError = NILEX_WIZARD_I18N.errors.max_images;
                        break;
                    }
                    if (!allowed.includes(file.type)) {
                        this.submitError = NILEX_WIZARD_I18N.errors.unsupported_format.replace(':name', file.name);
                        continue;
                    }
                    if (file.size > 5 * 1024 * 1024) {
                        this.submitError = NILEX_WIZARD_I18N.errors.image_too_large.replace(':name', file.name);
                        continue;
                    }
                    this.images.push(file);
                    this.imagePreviews.push({ url: URL.createObjectURL(file), name: file.name });
                }
            },
            removeImage(index) {
                URL.revokeObjectURL(this.imagePreviews[index].url);
                this.images.splice(index, 1);
                this.imagePreviews.splice(index, 1);
            },
            // وضع التعديل: حذف صورة حالية (تُسجَّل في removedImageIds لإرسالها للخادم).
            removeExistingImage(index) {
                const img = this.existingImages[index];
                if (!img) return;
                this.removedImageIds.push(img.id);
                this.existingImages.splice(index, 1);
            },

            // ── 🤖 AI Assistant (Gemini) ───────────────────────────────
            // يعيد استخدام GeminiService عبر مسار listings.ai-generate.
            // لا يكسر تدفق الويزارد: عند أي فشل تظهر رسالة عربية والمستخدم يكمل يدوياً.
            async generateWithAI() {
                const prompt = this.aiPrompt.trim();
                if (prompt.length < 3) {
                    this.aiMessage = { type: 'error', text: NILEX_WIZARD_I18N.ai.prompt_too_short };
                    return;
                }
                this.aiLoading = true;
                this.aiMessage = null;
                try {
                    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    const res = await fetch(NILEX_AI_URL, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ prompt }),
                    });

                    if (res.ok) {
                        const data = await res.json();
                        if (data.success && data.data) {
                            if (data.data.title) {
                                this.formData.title = data.data.title;
                                delete this.errors.title;
                            }
                            if (data.data.description) {
                                this.formData.description = data.data.description;
                                delete this.errors.description;
                            }
                            if (data.data.suggested_price && Number(data.data.suggested_price) > 0) {
                                this.formData.price = String(data.data.suggested_price);
                                delete this.errors.price;
                            }
                            this.aiMessage = { type: 'success', text: NILEX_WIZARD_I18N.ai.success };
                        } else {
                            this.aiMessage = { type: 'error', text: data.message || NILEX_WIZARD_I18N.ai.failed };
                        }
                    } else if (res.status === 422) {
                        this.aiMessage = { type: 'error', text: NILEX_WIZARD_I18N.ai.invalid_prompt };
                    } else if (res.status === 419) {
                        this.aiMessage = { type: 'error', text: NILEX_WIZARD_I18N.errors.session_expired };
                    } else {
                        this.aiMessage = { type: 'error', text: NILEX_WIZARD_I18N.ai.connection_failed };
                    }
                } catch (e) {
                    this.aiMessage = { type: 'error', text: NILEX_WIZARD_I18N.ai.connection_failed };
                } finally {
                    this.aiLoading = false;
                }
            },

            // ── Labels ─────────────────────────────────────────────────
            conditionLabel() {
                return this.formData.condition === 'new'
                    ? NILEX_WIZARD_I18N.step2.condition_new + ' ✨'
                    : NILEX_WIZARD_I18N.step2.condition_used + ' 🔄';
            },
            priceTypeLabel() {
                const found = this.priceTypes.find(p => p.value === this.formData.price_type);
                return found ? found.label : '';
            },

            // ── Submit ─────────────────────────────────────────────────
            async submitForm() {
                this.submitError = '';

                for (const step of [1, 2, 4]) {
                    if (!this.validateStep(step)) {
                        this.currentStep = step;
                        this.scrollTop();
                        return;
                    }
                }

                this.isSubmitting = true;

                const isEdit = NILEX_WIZARD_MODE === 'edit';

                const fd = new FormData();
                fd.append('title', this.formData.title);
                fd.append('description', this.formData.description);
                fd.append('price', this.formData.price);
                // الحالة (جديد/مستعمل) لا تُرسَل لقسم العقارات — الخادم يقبلها null هناك.
                if (!this.isRealEstateCategory) {
                    fd.append('condition', this.formData.condition);
                }
                fd.append('price_type', this.formData.price_type);
                fd.append('phone', this.formData.phone);
                fd.append('location_id', this.formData.location_id);

                // القسم والتمييز يُرسَلان في الإنشاء فقط — في التعديل القسم مقفول
                // والتمييز مستثنى (والخادم يتجاهلهما أصلاً في update).
                if (!isEdit) {
                    fd.append('category_id', this.formData.category_id);
                    fd.append('feature_days', this.formData.feature_days);
                }

                // 🚗 حقول السيارة (FK columns) — تُرسل فقط لقسم السيارات
                if (this.isCarCategory) {
                    fd.append('car_brand_id', this.formData.car_brand_id);
                    fd.append('car_model_id', this.formData.car_model_id);
                }

                for (const [key, value] of Object.entries(this.formData.custom_fields)) {
                    fd.append('custom_fields_values[' + key + ']', value === true ? '1' : (value === false ? '0' : value));
                }
                this.images.forEach(file => fd.append('images[]', file));

                // وضع التعديل: تزييف PUT + إرسال معرّفات الصور الحالية المحذوفة.
                if (isEdit) {
                    fd.append('_method', 'PUT');
                    this.removedImageIds.forEach(id => fd.append('removed_image_ids[]', id));
                }

                try {
                    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    const res = await fetch(isEdit ? NILEX_UPDATE_URL : NILEX_STORE_URL, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: fd,
                    });

                    if (res.ok) {
                        this.clearLocalStorage();
                        window.location.href = (res.redirected && res.url) ? res.url : NILEX_DASHBOARD_URL;
                        return;
                    }

                    if (res.status === 422) {
                        const data = await res.json();
                        this.mapServerErrors(data.errors || {});
                    } else if (res.status === 419) {
                        this.submitError = NILEX_WIZARD_I18N.errors.session_expired;
                    } else {
                        this.submitError = NILEX_WIZARD_I18N.errors.unexpected;
                    }
                } catch (e) {
                    this.submitError = NILEX_WIZARD_I18N.errors.network;
                } finally {
                    this.isSubmitting = false;
                }
            },
            mapServerErrors(errs) {
                this.errors = {};
                let targetStep = this.currentStep;
                for (const key in errs) {
                    const msg = Array.isArray(errs[key]) ? errs[key][0] : errs[key];
                    if (['title', 'description', 'price', 'car_brand_id', 'car_model_id'].includes(key)) {
                        this.errors[key] = msg;
                        targetStep = 2;
                    } else if (key === 'category_id') {
                        this.errors.category_id = msg;
                        targetStep = 1;
                    } else if (key.startsWith('custom_fields_values.')) {
                        const cfName = key.substring('custom_fields_values.'.length);
                        // car/real-estate-specific JSON keys bind to bare error keys in the wizard
                        if (['fuel', 'transmission', 'car_brand_other', 'year', 'condition', 'property_type', 'listing_type'].includes(cfName)) {
                            this.errors[cfName] = msg;
                        } else {
                            this.errors['cf_' + cfName] = msg;
                        }
                        targetStep = 2;
                    } else if (key === 'phone' || key === 'location_id') {
                        this.errors[key] = msg;
                        targetStep = 4;
                    } else if (key === 'images' || key.startsWith('images.') || key === 'removed_image_ids' || key.startsWith('removed_image_ids.')) {
                        // أخطاء الصور الخادمية (نوع/حجم) — أعرضها في خطوة الصور.
                        this.errors[key] = msg;
                        targetStep = 3;
                    } else {
                        this.errors[key] = msg;
                    }
                }
                this.currentStep = targetStep;
                this.submitError = NILEX_WIZARD_I18N.errors.fix_errors;
                this.scrollTop();
            },

            // ── LocalStorage ───────────────────────────────────────────
            saveToLocalStorage() {
                try {
                    localStorage.setItem(NILEX_WIZARD_KEY, JSON.stringify({
                        formData: this.formData,
                        selectedRootId: this.selectedRootId,
                    }));
                } catch (e) { /* ignore quota errors */ }
            },
            loadFromLocalStorage() {
                try {
                    const raw = localStorage.getItem(NILEX_WIZARD_KEY);
                    if (!raw) return;
                    const data = JSON.parse(raw);
                    if (data.formData) {
                        Object.assign(this.formData, data.formData);
                        if (!this.formData.custom_fields) this.formData.custom_fields = {};
                    }
                    if (data.selectedRootId) this.selectedRootId = data.selectedRootId;
                } catch (e) { /* ignore parse errors */ }
            },
            clearLocalStorage() {
                try { localStorage.removeItem(NILEX_WIZARD_KEY); } catch (e) {}
            },
        };
    }
</script>
@endpush
