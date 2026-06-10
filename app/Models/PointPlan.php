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
}
