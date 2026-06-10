<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignLink extends Model
{
    protected $fillable = [
        'code',
        'points_reward',
        'expires_at',
        'usage_limit',
        'used_count',
        'is_active',
    ];

    protected $casts = [
        'points_reward' => 'integer',
        'expires_at'    => 'datetime',
        'usage_limit'   => 'integer',
        'used_count'    => 'integer',
        'is_active'     => 'boolean',
    ];

    public function isValid(): bool
    {
        if (! $this->is_active) {
            return false;
        }
        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }
        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
