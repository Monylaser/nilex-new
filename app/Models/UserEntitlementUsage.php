<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserEntitlementUsage extends Model
{
    protected $table = 'user_entitlement_usage';

    protected $fillable = [
        'user_id',
        'feature_key',
        'period_key',
        'used_count',
    ];

    protected function casts(): array
    {
        return [
            'used_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
