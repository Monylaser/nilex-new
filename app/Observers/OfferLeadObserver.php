<?php

namespace App\Observers;

use App\Models\Offer;
use App\Services\SellerLeadService;

class OfferLeadObserver
{
    public function __construct(private SellerLeadService $sellerLeadService) {}

    public function created(Offer $offer): void
    {
        $this->sellerLeadService->createFromOffer($offer);
    }
}
