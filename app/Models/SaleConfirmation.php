<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SaleConfirmation extends Model
{
    // ── Status constants ────────────────────────────────────────────────────────
    // pending   : البائع أكّد (وقت الإنشاء) وفي انتظار المشتري
    // confirmed : الطرفان أكّدا — مقفول نهائياً (لا إلغاء بعده)
    // canceled  : البائع ألغى يدوياً من pending فقط
    public const STATUS_PENDING   = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CANCELED  = 'canceled';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_CONFIRMED,
        self::STATUS_CANCELED,
    ];

    protected $fillable = [
        'listing_id',
        'seller_id',
        'buyer_id',
        'status',
        'seller_confirmed_at',
        'buyer_confirmed_at',
        'canceled_at',
        'canceled_by',
    ];

    protected function casts(): array
    {
        return [
            'seller_confirmed_at' => 'datetime',
            'buyer_confirmed_at'  => 'datetime',
            'canceled_at'         => 'datetime',
        ];
    }

    /**
     * التأكيد الكامل = الطرفان أكّدا. مصدر الحقيقة هو الـ timestamps،
     * بينما عمود status نسخة مفهرسة منها لتسهيل الاستعلام.
     */
    public function isFullyConfirmed(): bool
    {
        return $this->seller_confirmed_at !== null
            && $this->buyer_confirmed_at !== null;
    }

    // ── State machine ───────────────────────────────────────────────────────
    // pending → confirmed (المشتري يؤكد) | pending → canceled (البائع يلغي).
    // لا انتقال من confirmed (مقفول نهائياً) أو من canceled.

    /**
     * حماية "مشتري واحد لكل إعلان": هل يمكن بدء تأكيد جديد لهذا الإعلان؟
     * يُمنع لو يوجد بالفعل تأكيد مكتمل (confirmed) لنفس الإعلان.
     * يستدعيها إجراء Livewire (Step 2) قبل الإنشاء.
     */
    public static function canInitiateForListing(int $listingId): bool
    {
        return ! self::query()
            ->where('listing_id', $listingId)
            ->where('status', self::STATUS_CONFIRMED)
            ->exists();
    }

    /**
     * المشتري يؤكد البيع. مسموح فقط من حالة pending.
     * يضبط buyer_confirmed_at و status = confirmed. يعيد false لو غير مسموح.
     */
    public function confirmByBuyer(): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        $this->forceFill([
            'buyer_confirmed_at' => now(),
            'status'             => self::STATUS_CONFIRMED,
        ])->save();

        return true;
    }

    /**
     * البائع يلغي يدوياً. مسموح فقط من حالة pending — لا إلغاء بعد confirmed.
     * يعيد false لو غير مسموح (confirmed/canceled).
     */
    public function cancelBySeller(?int $byUserId = null): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        $this->forceFill([
            'canceled_at' => now(),
            'canceled_by' => $byUserId ?? $this->seller_id,
            'status'      => self::STATUS_CANCELED,
        ])->save();

        return true;
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    // الإعلان soft-deleted بعد الإغلاق، لذا withTrashed إجباري لبقاء العلاقة شغالة
    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class)->withTrashed();
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function canceledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'canceled_by');
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }
}
