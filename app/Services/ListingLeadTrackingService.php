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

    // ─── Views ───────────────────────────────────────────────────────────────

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
            'listing_id' => $listing->id,
            'user_id'    => $user?->id,
            'ip_address' => $ipAddress,
        ]);

        return true;
    }

    // ─── Phone Clicks ─────────────────────────────────────────────────────────

    public function recordPhoneClick(
        Listing $listing,
        ?User $user = null,
        ?string $ipAddress = null
    ): bool {
        if ($user !== null && $this->recentPhoneClickExistsForUser($listing, $user)) {
            return false;
        }

        if ($user === null && $ipAddress !== null && $this->recentPhoneClickExistsForIp($listing, $ipAddress)) {
            return false;
        }

        ListingPhoneClick::query()->create([
            'listing_id' => $listing->id,
            'user_id'    => $user?->id,
            'ip_address' => $ipAddress,
        ]);

        return true;
    }

    // ─── WhatsApp Clicks ──────────────────────────────────────────────────────

    public function recordWhatsappClick(
        Listing $listing,
        ?User $user = null,
        ?string $ipAddress = null
    ): bool {
        if ($user !== null && $this->recentWhatsappClickExistsForUser($listing, $user)) {
            return false;
        }

        if ($user === null && $ipAddress !== null && $this->recentWhatsappClickExistsForIp($listing, $ipAddress)) {
            return false;
        }

        ListingWhatsappClick::query()->create([
            'listing_id' => $listing->id,
            'user_id'    => $user?->id,
            'ip_address' => $ipAddress,
        ]);

        return true;
    }

    // ─── Private: Views ───────────────────────────────────────────────────────

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

    // ─── Private: Phone Clicks ────────────────────────────────────────────────

    private function recentPhoneClickExistsForUser(Listing $listing, User $user): bool
    {
        return ListingPhoneClick::query()
            ->where('listing_id', $listing->id)
            ->where('user_id', $user->id)
            ->where('created_at', '>=', now()->subHours(self::CLICK_DEDUP_HOURS))
            ->exists();
    }

    private function recentPhoneClickExistsForIp(Listing $listing, string $ipAddress): bool
    {
        return ListingPhoneClick::query()
            ->where('listing_id', $listing->id)
            ->where('ip_address', $ipAddress)
            ->where('created_at', '>=', now()->subHours(self::CLICK_DEDUP_HOURS))
            ->exists();
    }

    // ─── Private: WhatsApp Clicks ─────────────────────────────────────────────

    private function recentWhatsappClickExistsForUser(Listing $listing, User $user): bool
    {
        return ListingWhatsappClick::query()
            ->where('listing_id', $listing->id)
            ->where('user_id', $user->id)
            ->where('created_at', '>=', now()->subHours(self::CLICK_DEDUP_HOURS))
            ->exists();
    }

    private function recentWhatsappClickExistsForIp(Listing $listing, string $ipAddress): bool
    {
        return ListingWhatsappClick::query()
            ->where('listing_id', $listing->id)
            ->where('ip_address', $ipAddress)
            ->where('created_at', '>=', now()->subHours(self::CLICK_DEDUP_HOURS))
            ->exists();
    }
}