<?php

namespace App\Services;

use App\Models\Listing;
use App\Models\ListingPhoneClick;
use App\Models\ListingView;
use App\Models\ListingWhatsappClick;
use App\Models\User;

class ListingLeadTrackingService
{
    private const VIEW_DEDUP_HOURS = 24;

    private const CLICK_DEDUP_HOURS = 1;

    public function recordView(Listing $listing, ?User $user = null, ?string $ipAddress = null): bool
    {
        if ($user !== null) {
            if ($this->recentViewExistsForUser($listing, $user)) {
                return false;
            }
        } elseif ($ipAddress !== null) {
            if ($this->recentViewExistsForIp($listing, $ipAddress)) {
                return false;
            }
        }

        ListingView::query()->create([
            'listing_id'  => $listing->id,
            'user_id'     => $user?->id,
            'ip_address'  => $ipAddress,
        ]);

        return true;
    }

    public function recordPhoneClick(Listing $listing, ?User $user = null): bool
    {
        if ($user !== null && $this->recentPhoneClickExists($listing, $user)) {
            return false;
        }

        ListingPhoneClick::query()->create([
            'listing_id' => $listing->id,
            'user_id'    => $user?->id,
        ]);

        return true;
    }

    public function recordWhatsappClick(Listing $listing, ?User $user = null): bool
    {
        if ($user !== null && $this->recentWhatsappClickExists($listing, $user)) {
            return false;
        }

        ListingWhatsappClick::query()->create([
            'listing_id' => $listing->id,
            'user_id'    => $user?->id,
        ]);

        return true;
    }

    private function recentViewExistsForUser(Listing $listing, User $user): bool
    {
        return ListingView::query()
            ->where('listing_id', $listing->id)
            ->where('user_id', $user->id)
            ->where('created_at', '>=', now()->subHours(self::VIEW_DEDUP_HOURS))
            ->exists();
    }

    private function recentViewExistsForIp(Listing $listing, string $ipAddress): bool
    {
        return ListingView::query()
            ->where('listing_id', $listing->id)
            ->where('ip_address', $ipAddress)
            ->where('created_at', '>=', now()->subHours(self::VIEW_DEDUP_HOURS))
            ->exists();
    }

    private function recentPhoneClickExists(Listing $listing, User $user): bool
    {
        return ListingPhoneClick::query()
            ->where('listing_id', $listing->id)
            ->where('user_id', $user->id)
            ->where('created_at', '>=', now()->subHours(self::CLICK_DEDUP_HOURS))
            ->exists();
    }

    private function recentWhatsappClickExists(Listing $listing, User $user): bool
    {
        return ListingWhatsappClick::query()
            ->where('listing_id', $listing->id)
            ->where('user_id', $user->id)
            ->where('created_at', '>=', now()->subHours(self::CLICK_DEDUP_HOURS))
            ->exists();
    }
}
