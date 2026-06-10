<?php

namespace App\Observers;

use App\Models\Listing;

class ListingObserver
{
    /**
     * Arabic spam / fraud keywords that trigger auto-flagging.
     * Matched via case-insensitive multi-byte substring search.
     */
    public const FRAUD_KEYWORDS = [
        'نصب',
        'احتيال',
        'مسروق',
        'مضروب',
        'مزور',
        'وهمي',
        'كاش فوري',
        'ربح سريع',
        'تحويل فوري',
        'دولار رخيص',
        'استثمار مضمون',
    ];

    public function creating(Listing $listing): void
    {
        $this->applyFraudCheck($listing);
    }

    public function updating(Listing $listing): void
    {
        $this->applyFraudCheck($listing);
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function applyFraudCheck(Listing $listing): void
    {
        $haystack = ($listing->title ?? '') . ' ' . ($listing->description ?? '');

        foreach (self::FRAUD_KEYWORDS as $keyword) {
            if (mb_stripos($haystack, $keyword) !== false) {
                $listing->is_flagged  = true;
                $listing->flag_reason = 'auto_fraud: ' . $keyword;
                $listing->status      = Listing::STATUS_PENDING;
                return;
            }
        }
    }
}
