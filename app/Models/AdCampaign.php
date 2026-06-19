<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class AdCampaign extends Model implements HasMedia
{
    use InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'title',
        'placement',
        'category_id',
        'target_url',
        'duration_days',
        'status',
        'approval_status',
        'rejected_reason',
        'starts_at',
        'ends_at',
        'views_count',
        'clicks_count',
        'priority',
        'created_by',
        'seller_id',
        'payment_status',
        'paymob_order_id',
        'paymob_transaction_id',
        'amount_paid',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at'       => 'datetime',
            'ends_at'         => 'datetime',
            'paid_at'         => 'datetime',
            'approval_status' => 'string',
            'status'          => 'string',
            'placement'       => 'string',
            'payment_status'  => 'string',
            'duration_days'   => 'integer',
            'views_count'     => 'integer',
            'clicks_count'    => 'integer',
            'priority'        => 'integer',
            'amount_paid'     => 'decimal:2',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('ad_image')
            ->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('desktop')
            ->fit(Fit::Crop, 1200, 400)
            ->format('webp')
            ->nonQueued();

        $this->addMediaConversion('tablet')
            ->fit(Fit::Crop, 768, 256)
            ->format('webp')
            ->nonQueued();

        $this->addMediaConversion('mobile')
            ->fit(Fit::Crop, 390, 130)
            ->format('webp')
            ->nonQueued();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(AdCampaignLog::class);
    }

    public function paymentAttempts(): HasMany
    {
        return $this->hasMany(PaymentAttempt::class, 'campaign_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AdCampaignAuditLog::class, 'campaign_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('status', 'active')
            ->where('approval_status', 'approved')
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now());
    }

    public function scopeDisplayable(Builder $query): Builder
    {
        return $query
            ->where('approval_status', 'approved')
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->where(function (Builder $paymentQuery) {
                $paymentQuery
                    ->whereNull('payment_status')
                    ->orWhere('payment_status', 'paid');
            });
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('payment_status', 'paid');
    }

    public function scopeByPlacement(Builder $query, string $placement): Builder
    {
        return $query->where('placement', $placement);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('approval_status', 'pending');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('approval_status', 'approved');
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('approval_status', 'rejected');
    }

    public function getCtrAttribute(): float
    {
        return round(($this->clicks_count / max($this->views_count, 1)) * 100, 2);
    }

    public function getIsApprovedAttribute(): bool
    {
        return $this->approval_status === 'approved';
    }

    public function getIsPendingAttribute(): bool
    {
        return $this->approval_status === 'pending';
    }

    public function getIsRejectedAttribute(): bool
    {
        return $this->approval_status === 'rejected';
    }

    public function getIsPaidAttribute(): bool
    {
        return $this->payment_status === 'paid'
            || ($this->payment_status === null && $this->seller_id === null);
    }

    public function isDisplayable(): bool
    {
        if ($this->approval_status !== 'approved') {
            return false;
        }

        if ($this->starts_at === null || $this->ends_at === null) {
            return false;
        }

        if ($this->starts_at->isFuture() || $this->ends_at->isPast()) {
            return false;
        }

        if ($this->payment_status !== null && $this->payment_status !== 'paid') {
            return false;
        }

        return true;
    }

    public function isTrackable(): bool
    {
        return $this->status === 'active'
            && $this->approval_status === 'approved'
            && $this->isDisplayable();
    }

    public function approve(): void
    {
        $this->update([
            'approval_status'  => 'approved',
            'rejected_reason'  => null,
        ]);
    }

    public function reject(string $reason): void
    {
        $this->update([
            'approval_status' => 'rejected',
            'rejected_reason' => $reason,
        ]);
    }
}
