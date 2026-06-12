<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserEntitlement extends Model
{
    public const SOURCE_PURCHASE = 'purchase';

    public const SOURCE_ADMIN = 'admin_grant';

    public const SOURCE_MIGRATION = 'migration_default';

    protected $fillable = [
        'user_id',
        'feature_key',
        'value_type',
        'value',
        'source',
        'source_plan_id',
        'source_transaction_id',
        'granted_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'granted_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sourcePlan(): BelongsTo
    {
        return $this->belongsTo(PointPlan::class, 'source_plan_id');
    }

    public function sourceTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'source_transaction_id');
    }

    public function castValue(): bool|int|string
    {
        return match ($this->value_type) {
            PlanEntitlement::TYPE_BOOLEAN => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            PlanEntitlement::TYPE_INTEGER => (int) $this->value,
            default                       => (string) $this->value,
        };
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
