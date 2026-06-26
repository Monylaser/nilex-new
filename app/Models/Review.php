<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $fillable = [
        'sale_confirmation_id',
        'reviewer_id',
        'reviewee_id',
        'listing_id',
        'rating',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function saleConfirmation(): BelongsTo
    {
        return $this->belongsTo(SaleConfirmation::class);
    }

    // المشتري (كاتب التقييم)
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    // البائع (المُقيَّم)
    public function reviewee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewee_id');
    }

    // الإعلان soft-deleted بعد الإغلاق، لذا withTrashed إجباري
    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class)->withTrashed();
    }
}
