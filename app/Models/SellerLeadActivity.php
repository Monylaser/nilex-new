<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellerLeadActivity extends Model
{
    public const UPDATED_AT = null;

    public const TYPE_CREATED = 'created';

    public const TYPE_STATUS_CHANGED = 'status_changed';

    protected $fillable = [
        'seller_lead_id',
        'type',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata'   => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function sellerLead(): BelongsTo
    {
        return $this->belongsTo(SellerLead::class);
    }
}
