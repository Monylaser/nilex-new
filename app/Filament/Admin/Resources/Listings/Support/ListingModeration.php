<?php

namespace App\Filament\Admin\Resources\Listings\Support;

use App\Models\AuditLog;
use App\Models\Listing;
use App\Notifications\ListingStatusNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Single source of truth for the admin listing approve/reject workflow.
 *
 * Previously the full path (model state change + AuditLog + user notification
 * + auto-strike) lived only in the table row actions, while the View page had
 * a divergent, simpler path (no AuditLog, no notification). Both the table and
 * the View page now call these helpers so the behavior is identical everywhere.
 */
class ListingModeration
{
    /**
     * Approve + publish a listing: state change, audit log, notify the owner.
     */
    public static function approve(Listing $listing): void
    {
        $listing->approve(Auth::id());

        AuditLog::record('approve_ad', $listing, ['title' => $listing->title]);

        self::notifyUser($listing, 'published');
    }

    /**
     * Reject a listing with a reason (+ optional note): state change, auto-strike
     * when the reason warrants it, audit log(s), notify the owner.
     *
     * @return string The human-readable rejection-reason label (for a flash/toast).
     */
    public static function reject(Listing $listing, string $reason, ?string $note = null): string
    {
        $listing->reject(Auth::id(), $reason);

        $causesStrike = $listing->rejectionCausesStrike($reason);

        if ($causesStrike) {
            $listing->user->addStrike();

            AuditLog::record('auto_strike', $listing->user, [
                'reason'       => $reason,
                'listing_id'   => $listing->id,
                'strike_count' => $listing->user->fresh()->strike_count,
            ]);
        }

        AuditLog::record('reject_ad', $listing, [
            'title'            => $listing->title,
            'rejection_reason' => $reason,
            'extra_note'       => $note,
            'causes_strike'    => $causesStrike ? 'yes' : 'no',
        ]);

        self::notifyUser($listing, 'rejected', $reason, $note);

        return Listing::rejectionReasonOptions()[$reason] ?? $reason;
    }

    protected static function notifyUser(
        Listing $listing,
        string $status,
        ?string $reason = null,
        ?string $note = null
    ): void {
        try {
            $listing->user->notify(
                new ListingStatusNotification($listing, $status, $reason, $note)
            );
        } catch (\Exception $e) {
            Log::error("Notify failed [{$listing->user->email}]: " . $e->getMessage());
        }
    }
}
