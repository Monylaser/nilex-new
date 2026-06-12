<?php

namespace App\Observers;

use App\Models\ListingWhatsappClick;
use App\Services\SellerLeadService;

class ListingWhatsappClickObserver
{
    public function __construct(private SellerLeadService $sellerLeadService) {}

    public function created(ListingWhatsappClick $click): void
    {
        $this->sellerLeadService->createFromWhatsappClick($click);
    }
}
