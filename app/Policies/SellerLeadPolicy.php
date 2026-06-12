<?php

namespace App\Policies;

use App\Models\SellerLead;
use App\Models\User;

class SellerLeadPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, SellerLead $sellerLead): bool
    {
        return $sellerLead->seller_id === $user->id;
    }

    public function update(User $user, SellerLead $sellerLead): bool
    {
        return $sellerLead->seller_id === $user->id;
    }
}
