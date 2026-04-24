<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Location extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    // الثوابت لتسهيل القراءة في الكود (محافظة = 0، مدينة = 1)
    public const LEVEL_GOVERNORATE = 0;
    public const LEVEL_CITY         = 1;

    protected $fillable = [
        'name_ar',
        'name_en',
        'slug',
        'parent_id',
        'level',
        'latitude',
        'longitude',
        'is_active',
        'sort_order',
    ];

    /**
     * تحويل أنواع البيانات تلقائياً (Casting)
     */
    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'level'      => 'integer',
            'sort_order' => 'integer',
            'latitude'   => 'decimal:7',
            'longitude'  => 'decimal:7',
        ];
    }

    /**
     * نظام الكاش التلقائي (الضربة القاضية للسرعة)
     */
    protected static function booted()
    {
        // أول ما أي محافظة تتعدل أو تتمسح، الكاش يتنظف تلقائياً لضمان دقة البيانات
        static::saved(fn () => Cache::forget('active_locations_list'));
        static::deleted(fn () => Cache::forget('active_locations_list'));
    }

    /**
     * جلب كل المواقع بنظام الكاش لسرعة استجابة فائقة
     */
    public static function getCachedAll()
    {
        return Cache::rememberForever('active_locations_list', function () {
            return self::active()->with('children')->orderBy('sort_order')->get();
        });
    }

    /**
     * معالجة الصور تلقائياً: تحويل لـ WebP وتصغير الحجم للأداء الخارق
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(200)
            ->height(200)
            ->format('webp')
            ->nonQueued();
    }

    // -----------------------------------------------------------------------
    // Accessors (الحصول على البيانات بشكل منسق)
    // -----------------------------------------------------------------------

    /** الحصول على الاسم حسب لغة الموقع */
    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name_en;
    }

    /** الحصول على الاسم الكامل (مثلاً: القاهرة، المعادي) */
    public function getFullNameArAttribute(): string
    {
        if ($this->parent) {
            return $this->parent->name_ar . '، ' . $this->name_ar;
        }
        return $this->name_ar;
    }

    // -----------------------------------------------------------------------
    // Relationships (العلاقات البرمجية)
    // -----------------------------------------------------------------------

    /** علاقة المدينة بالمحافظة الأم */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'parent_id');
    }

    /** علاقة المحافظة بالمدن التابعة لها */
    public function children(): HasMany
    {
        return $this->hasMany(Location::class, 'parent_id')->orderBy('sort_order');
    }

    // -----------------------------------------------------------------------
    // Scopes (تسهيل عمليات البحث والفلترة)
    // -----------------------------------------------------------------------

    /** لجلب المواقع النشطة فقط */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** لجلب المحافظات فقط */
    public function scopeGovernorates($query)
    {
        return $query->where('level', self::LEVEL_GOVERNORATE)->orderBy('sort_order');
    }

    /** لجلب المدن فقط */
    public function scopeCities($query)
    {
        return $query->where('level', self::LEVEL_CITY)->orderBy('sort_order');
    }
}
