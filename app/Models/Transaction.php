<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'plan_id',
        'amount',
        'status',
        'is_processed',
        'processed_at',
        'gateway_reference',
        'paymob_order_id',
    ];

    protected function casts(): array
    {
        return [
            'amount'       => 'decimal:2',
            'is_processed' => 'boolean',
            'processed_at'   => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(PointPlan::class, 'plan_id');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }
}
