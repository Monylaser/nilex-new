<?php

namespace App\Observers;

use App\Models\ListingPhoneClick;
use App\Services\SellerLeadService;

class ListingPhoneClickObserver
{
    public function __construct(private SellerLeadService $sellerLeadService) {}

    public function created(ListingPhoneClick $click): void
    {
        $this->sellerLeadService->createFromPhoneClick($click);
    }
}
