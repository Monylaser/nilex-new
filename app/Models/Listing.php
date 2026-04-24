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

    // بما إننا بنتحكم في الإدخال من الفيلالمينت، هنسمح بكل الحقول
    protected $guarded = [];

    /**
     * التحويلات التلقائية (Casts)
     * دي اللي هتحل مشكلة Array to string conversion
     */
    protected function casts(): array
    {
        return [
            'extra_images' => 'array', // تحويل تلقائي للـ Repeater من وإلى JSON
            'price' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'custom_fields_values' => 'array', // أضف هذا السطر ضروري جداً
        ];
    }

    /**
     * معالجة الصور تلقائياً: تحويل لـ WebP + تصغير الحجم للأداء الخارق
     */
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

    // -----------------------------------------------------------------------
    // Relationships (العلاقات)
    // -----------------------------------------------------------------------

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * علاقة المحافظة
     */
    public function province(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'province_id');
    }

    /**
     * علاقة المدينة (الموقع الدقيق)
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    // -----------------------------------------------------------------------
    // Scopes & Helpers
    // -----------------------------------------------------------------------

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
