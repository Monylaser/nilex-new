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
                $validator->errors()->add('category_id', __('server.ads.category_only_category_page'));
            }
        });
    }
}
