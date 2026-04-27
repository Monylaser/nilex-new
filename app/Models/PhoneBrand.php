<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PhoneBrand extends Model
{
    protected $fillable = ['name_ar', 'name_en', 'slug', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function models(): HasMany
    {
        return $this->hasMany(PhoneModel::class)->orderBy('sort_order');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}