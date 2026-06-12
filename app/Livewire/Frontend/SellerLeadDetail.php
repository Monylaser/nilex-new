<?php

namespace App\Livewire\Frontend;

use App\Models\Offer;
use App\Models\SellerLead;
use App\Services\SellerLeadService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class SellerLeadDetail extends Component
{
    public SellerLead $lead;

    public string $status = '';

    public function mount(SellerLead $lead): void
    {
        $this->authorize('view', $lead);

        $this->lead = $lead->load(['listing', 'buyer', 'activities']);
        $this->status = $lead->status;
    }

    public function updateStatus(SellerLeadService $sellerLeadService): void
    {
        $this->authorize('update', $this->lead);

        $this->lead = $sellerLeadService->updateStatus(
            $this->lead,
            $this->status,
            Auth::user(),
        );

        session()->flash('success', __('ui.leads.status_updated'));
    }

    public function render()
    {
        $sourceDetails = $this->resolveSourceDetails();

        return view('livewire.frontend.seller-lead-detail', [
            'sourceDetails' => $sourceDetails,
        ]);
    }

    private function resolveSourceDetails(): array
    {
        return match ($this->lead->source_type) {
            SellerLead::SOURCE_OFFER => $this->offerDetails(),
            SellerLead::SOURCE_PHONE_REVEAL => [
                'label'       => __('ui.leads.source_phone_reveal'),
                'description' => __('ui.leads.source_phone_reveal_desc'),
            ],
            SellerLead::SOURCE_WHATSAPP_CLICK => [
                'label'       => __('ui.leads.source_whatsapp_click'),
                'description' => __('ui.leads.source_whatsapp_click_desc'),
            ],
            default => [
                'label'       => $this->lead->source_type,
                'description' => '',
            ],
        };
    }

    private function offerDetails(): array
    {
        $offer = Offer::query()->find($this->lead->source_id);

        return [
            'label'       => __('ui.leads.source_offer'),
            'description' => __('ui.leads.source_offer_desc'),
            'amount'      => $offer?->amount,
            'message'     => $offer?->message,
            'offer_status'=> $offer?->status,
        ];
    }
}
