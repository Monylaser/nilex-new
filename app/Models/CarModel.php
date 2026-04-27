<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CarModel extends Model
{
    protected $fillable = ['car_brand_id', 'name_ar', 'name_en', 'slug', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Boot function to handle automatic slug generation
     */
    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name_en);
            }
        });
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(CarBrand::class, 'car_brand_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}