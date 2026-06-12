<?php

namespace App\Services;

use App\Models\ListingPhoneClick;
use App\Models\ListingWhatsappClick;
use App\Models\Offer;
use App\Models\SellerLead;
use App\Models\SellerLeadActivity;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SellerLeadService
{
    public function createFromPhoneReveal(ListingPhoneClick $click): ?SellerLead
    {
        return $this->createFromPhoneClick($click);
    }

    public function createFromPhoneClick(ListingPhoneClick $click): ?SellerLead
    {
        $click->loadMissing('listing');

        return $this->createLead(
            sellerId: (int) $click->listing->user_id,
            listingId: (int) $click->listing_id,
            buyerId: $click->user_id,
            sourceType: SellerLead::SOURCE_PHONE_REVEAL,
            sourceId: (int) $click->id,
        );
    }

    public function createFromWhatsappClick(ListingWhatsappClick $click): ?SellerLead
    {
        $click->loadMissing('listing');

        return $this->createLead(
            sellerId: (int) $click->listing->user_id,
            listingId: (int) $click->listing_id,
            buyerId: $click->user_id,
            sourceType: SellerLead::SOURCE_WHATSAPP_CLICK,
            sourceId: (int) $click->id,
        );
    }

    public function createFromOffer(Offer $offer): ?SellerLead
    {
        return $this->createLead(
            sellerId: (int) $offer->receiver_id,
            listingId: (int) $offer->listing_id,
            buyerId: (int) $offer->sender_id,
            sourceType: SellerLead::SOURCE_OFFER,
            sourceId: (int) $offer->id,
        );
    }

    public function updateStatus(SellerLead $lead, string $status, User $seller): SellerLead
    {
        if (! in_array($status, SellerLead::statuses(), true)) {
            throw new \InvalidArgumentException("Invalid lead status: {$status}");
        }

        if ($lead->seller_id !== $seller->id) {
            abort(403);
        }

        $previousStatus = $lead->status;

        if ($previousStatus === $status) {
            return $lead;
        }

        DB::transaction(function () use ($lead, $status, $previousStatus): void {
            $lead->update(['status' => $status]);

            $this->recordActivity($lead, SellerLeadActivity::TYPE_STATUS_CHANGED, [
                'from' => $previousStatus,
                'to'   => $status,
            ]);
        });

        return $lead->fresh(['listing', 'buyer', 'activities']);
    }

    public function backfillExistingEvents(?int $sellerId = null): array
    {
        $created = 0;
        $skipped = 0;

        $phoneQuery = ListingPhoneClick::query()->with('listing');
        $whatsappQuery = ListingWhatsappClick::query()->with('listing');
        $offerQuery = Offer::query();

        if ($sellerId !== null) {
            $phoneQuery->whereHas('listing', fn ($q) => $q->where('user_id', $sellerId));
            $whatsappQuery->whereHas('listing', fn ($q) => $q->where('user_id', $sellerId));
            $offerQuery->where('receiver_id', $sellerId);
        }

        foreach ($phoneQuery->cursor() as $click) {
            $lead = $this->createFromPhoneClick($click);
            $lead === null ? $skipped++ : $created++;
        }

        foreach ($whatsappQuery->cursor() as $click) {
            $lead = $this->createFromWhatsappClick($click);
            $lead === null ? $skipped++ : $created++;
        }

        foreach ($offerQuery->cursor() as $offer) {
            $lead = $this->createFromOffer($offer);
            $lead === null ? $skipped++ : $created++;
        }

        return ['created' => $created, 'skipped' => $skipped];
    }

    private function createLead(
        int $sellerId,
        int $listingId,
        ?int $buyerId,
        string $sourceType,
        int $sourceId,
    ): ?SellerLead {
        if (SellerLead::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->exists()) {
            return null;
        }

        return DB::transaction(function () use ($sellerId, $listingId, $buyerId, $sourceType, $sourceId): SellerLead {
            $lead = SellerLead::query()->create([
                'seller_id'   => $sellerId,
                'listing_id'  => $listingId,
                'buyer_id'    => $buyerId,
                'source_type' => $sourceType,
                'source_id'   => $sourceId,
                'status'      => SellerLead::STATUS_NEW,
            ]);

            $this->recordActivity($lead, SellerLeadActivity::TYPE_CREATED, [
                'source_type' => $sourceType,
                'source_id'   => $sourceId,
            ]);

            return $lead;
        });
    }

    private function recordActivity(SellerLead $lead, string $type, array $metadata = []): void
    {
        SellerLeadActivity::query()->create([
            'seller_lead_id' => $lead->id,
            'type'           => $type,
            'metadata'       => $metadata,
        ]);
    }
}
