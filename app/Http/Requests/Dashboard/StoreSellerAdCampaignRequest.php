<?php

namespace App\Http\Requests\Dashboard;

use App\Services\AdCampaignPaymentService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreSellerAdCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $allowedPlacements = ['hero_top', 'home_feed', 'category_page', 'login_page', 'popup'];
        $allowedDurations  = app(AdCampaignPaymentService::class)->getAvailableDurations();

        return [
            'title'         => ['required', 'string', 'max:255'],
            'placement'     => ['required', 'string', Rule::in($allowedPlacements)],
            'category_id'   => [
                Rule::requiredIf(fn () => $this->input('placement') === 'category_page'),
                'nullable',
                'integer',
                'exists:categories,id',
            ],
            'target_url'    => ['required', 'url', 'max:2048'],
            'duration_days' => ['required', 'integer', Rule::in($allowedDurations)],
            'ad_image'      => [
                'required',
                'file',
                'mimes:jpeg,jpg,png,webp,gif',
                'max:2048',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->input('placement') !== 'category_page' && $this->filled('category_id')) {
                $validator->errors()->add('category_id', 'التصنيف متاح فقط لبانر صفحة التصنيف.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'title.required'         => 'عنوان الحملة مطلوب.',
            'placement.required'     => 'موضع الإعلان مطلوب.',
            'placement.in'           => 'موضع الإعلان غير صالح.',
            'category_id.required'   => 'التصنيف مطلوب لبانر صفحة التصنيف.',
            'category_id.exists'     => 'التصنيف المحدد غير موجود.',
            'target_url.required'    => 'رابط الهدف مطلوب.',
            'target_url.url'         => 'رابط الهدف غير صالح.',
            'duration_days.required' => 'مدة الحملة مطلوبة.',
            'duration_days.in'       => 'مدة الحملة غير صالحة.',
            'ad_image.required'      => 'صورة البانر مطلوبة.',
            'ad_image.mimes'         => 'يجب أن تكون الصورة بصيغة jpeg أو png أو webp أو gif.',
            'ad_image.max'           => 'حجم الصورة يجب ألا يتجاوز 2 ميجابايت.',
        ];
    }
}
