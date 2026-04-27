<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Image\Enums\Fit;

class Listing extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'extra_images'         => 'array',
            'price'                => 'decimal:2',
            'custom_fields_values' => 'array',
            'created_at'           => 'datetime',
            'updated_at'           => 'datetime',
        ];
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Fit::Contain, 300, 300)
            ->format('webp')
            ->nonQueued();

        $this->addMediaConversion('full_hd')
            ->fit(Fit::Max, 1920, 1080)
            ->format('webp')
            ->quality(80)
            ->nonQueued();
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'province_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}