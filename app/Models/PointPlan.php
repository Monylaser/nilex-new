<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PointPlan extends Model
{
    protected $fillable = [
        'name_ar',
        'name_en',
        'points',
        'price',
        'description',
        'is_active',
        'tier_key',
    ];

    protected $casts = [
        'points'    => 'integer',
        'price'     => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'plan_id');
    }

    public function resolveTierKey(): ?string
    {
        if ($this->tier_key) {
            return $this->tier_key;
        }

        $en = strtolower(trim($this->name_en ?? ''));
        $ar = trim($this->name_ar ?? '');

        if (str_contains($en, 'starter') || str_contains($ar, 'مبتد')) {
            return PlanEntitlement::TIER_STARTER;
        }

        if (str_contains($en, 'growth') || str_contains($ar, 'نمو')) {
            return PlanEntitlement::TIER_GROWTH;
        }

        if (str_contains($en, 'pro') || str_contains($ar, 'محترف')) {
            return PlanEntitlement::TIER_PRO_SELLER;
        }

        if (str_contains($en, 'business') || str_contains($ar, 'أعمال') || str_contains($ar, 'شرك')) {
            return PlanEntitlement::TIER_BUSINESS;
        }

        return null;
    }
}
