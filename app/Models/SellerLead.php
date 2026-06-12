<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SellerLead extends Model
{
    public const SOURCE_PHONE_REVEAL = 'phone_reveal';

    public const SOURCE_WHATSAPP_CLICK = 'whatsapp_click';

    public const SOURCE_OFFER = 'offer';

    public const STATUS_NEW = 'new';

    public const STATUS_CONTACTED = 'contacted';

    public const STATUS_QUALIFIED = 'qualified';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'seller_id',
        'listing_id',
        'buyer_id',
        'source_type',
        'source_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public static function sourceTypes(): array
    {
        return [
            self::SOURCE_PHONE_REVEAL,
            self::SOURCE_WHATSAPP_CLICK,
            self::SOURCE_OFFER,
        ];
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_NEW,
            self::STATUS_CONTACTED,
            self::STATUS_QUALIFIED,
            self::STATUS_CLOSED,
        ];
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(SellerLeadActivity::class)->orderBy('created_at');
    }

    public function scopeForSeller(Builder $query, User $seller): Builder
    {
        return $query->where('seller_id', $seller->id);
    }

    public function scopeInPeriod(Builder $query, ?string $period): Builder
    {
        return match ($period) {
            'today'  => $query->where('created_at', '>=', now()->startOfDay()),
            '7days'  => $query->where('created_at', '>=', now()->subDays(7)->startOfDay()),
            '30days' => $query->where('created_at', '>=', now()->subDays(30)->startOfDay()),
            default  => $query,
        };
    }
}
