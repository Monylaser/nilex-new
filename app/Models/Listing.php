<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Image\Enums\AlignPosition;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Enums\Unit;
use Laravel\Scout\Searchable;

class Listing extends Model implements HasMedia
{
    use InteractsWithMedia, Searchable, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'status', 'price', 'rejection_reason', 'is_featured'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $event) => "Listing {$event}");
    }

    protected $guarded = [];

    // ── Status Constants ──────────────────────────────────────────────────────

    const STATUS_PENDING   = 'pending';
    const STATUS_PUBLISHED = 'published';
    const STATUS_REJECTED  = 'rejected';
    const STATUS_FLAGGED   = 'flagged';

    // ── Rejection Reason Constants ────────────────────────────────────────────

    const REASON_INAPPROPRIATE  = 'inappropriate_content';
    const REASON_SCAM           = 'scam_fraud';
    const REASON_INCOMPLETE     = 'incomplete_info';
    const REASON_WRONG_CATEGORY = 'wrong_category';
    const REASON_PROHIBITED     = 'prohibited_items';
    const REASON_DUPLICATE      = 'duplicate';

    const STRIKE_REASONS = [
        self::REASON_INAPPROPRIATE,
        self::REASON_SCAM,
        self::REASON_PROHIBITED,
    ];

    // تكاليف التمييز بالنقاط — لا تعتمد على سعر يومي ثابت
    // يجب أن تطابق القيم المعروضة في صفحة /pricing
    const FEATURE_COSTS = [
        1  => 25,
        3  => 60,
        7  => 120,
        14 => 220,
    ];

    // ── Options ───────────────────────────────────────────────────────────────

    public static function statusOptions(): array
    {
        return [
            self::STATUS_PENDING   => 'قيد المراجعة',
            self::STATUS_PUBLISHED => 'منشور',
            self::STATUS_REJECTED  => 'مرفوض',
            self::STATUS_FLAGGED   => 'مُبلَّغ عنه',
        ];
    }

    public static function rejectionReasonOptions(): array
    {
        return [
            self::REASON_INAPPROPRIATE  => '🚫 محتوى غير لائق — مخالفة أخلاقية أو دينية',
            self::REASON_SCAM           => '⚠️ احتيال أو نصب — سعر مريب أو بيانات مزورة',
            self::REASON_INCOMPLETE     => '📝 معلومات ناقصة — وصف غير كافٍ أو صور غير واضحة',
            self::REASON_WRONG_CATEGORY => '📂 قسم خاطئ — الإعلان في القسم غير المناسب',
            self::REASON_PROHIBITED     => '⛔ منتج محظور — مخالف للقانون المصري',
            self::REASON_DUPLICATE      => '🔁 إعلان مكرر — موجود بالفعل على المنصة',
        ];
    }

    // ── Casts ─────────────────────────────────────────────────────────────────

    protected function casts(): array
    {
        return [
            'extra_images'         => 'array',
            'price'                => 'decimal:2',
            'custom_fields_values' => 'array',
            'moderated_at'         => 'datetime',
            'featured_until'       => 'datetime',
            'is_featured'          => 'boolean',
            'is_flagged'           => 'boolean',
            'created_at'           => 'datetime',
            'updated_at'           => 'datetime',
        ];
    }

    // ── Media ─────────────────────────────────────────────────────────────────

    public function registerMediaConversions(?Media $media = null): void
    {
        // 1. الصورة المصغرة (لوحة التحكم + شريط المعرض) — مع علامة مائية خفيفة
        // كل نسخة تُعرض للجمهور تحمل العلامة المائية: لا توجد صورة نظيفة معروضة.
        $this->addMediaConversion('thumb')
            ->fit(Fit::Contain, 300, 300)
            ->watermark(
                public_path('images/watermark.png'),
                AlignPosition::BottomRight,
                8,           // paddingX
                8,           // paddingY
                Unit::Pixel,
                80,          // width
                Unit::Pixel,
                0,           // height (auto)
                Unit::Pixel,
                Fit::Contain,
                40,          // alpha / opacity (0–100)
            )
            ->format('webp')
            ->nonQueued();

        // 2. صورة الكروت (الرئيسية / الكروت / نتائج البحث) — مع علامة مائية
        $this->addMediaConversion('card')
            ->fit(Fit::Crop, 600, 450)
            ->watermark(
                public_path('images/watermark.png'),
                AlignPosition::BottomRight,
                14,          // paddingX
                14,          // paddingY
                Unit::Pixel,
                120,         // width
                Unit::Pixel,
                0,           // height (auto)
                Unit::Pixel,
                Fit::Contain,
                40,          // alpha / opacity (0–100)
            )
            ->format('webp')
            ->quality(80)
            ->nonQueued();

        // 3. الصورة الكاملة (صفحة التفاصيل) — مع علامة مائية
        $this->addMediaConversion('full_hd')
            ->fit(Fit::Max, 1920, 1080)
            ->watermark(
                public_path('images/watermark.png'),
                AlignPosition::BottomRight,
                20,          // paddingX
                20,          // paddingY
                Unit::Pixel,
                150,         // width
                Unit::Pixel,
                0,           // height (auto)
                Unit::Pixel,
                Fit::Contain,
                40,          // alpha / opacity (0–100)
            )
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

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'province_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function carBrand(): BelongsTo
    {
        return $this->belongsTo(CarBrand::class, 'car_brand_id');
    }

    public function carModel(): BelongsTo
    {
        return $this->belongsTo(CarModel::class, 'car_model_id');
    }

    public function views(): HasMany
    {
        return $this->hasMany(ListingView::class);
    }

    public function phoneClicks(): HasMany
    {
        return $this->hasMany(ListingPhoneClick::class);
    }

    public function whatsappClicks(): HasMany
    {
        return $this->hasMany(ListingWhatsappClick::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function scopePending(Builder $query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeFlagged(Builder $query)
    {
        return $query->where('status', self::STATUS_FLAGGED);
    }

    public function scopeFeatured(Builder $query)
    {
        return $query->where('is_featured', true)
             ->where('featured_until', '>=', now());
    }

    // ── Moderation Helpers ────────────────────────────────────────────────────

    public function approve(int $adminId): void
    {
        $this->update([
            'status'       => self::STATUS_PUBLISHED,
            'moderated_by' => $adminId,
            'moderated_at' => now(),
        ]);
    }

    public function reject(int $adminId, string $reason): void
    {
        $this->update([
            'status'           => self::STATUS_REJECTED,
            'rejection_reason' => $reason,
            'moderated_by'     => $adminId,
            'moderated_at'     => now(),
        ]);
    }

    public function flag(int $adminId, string $reason): void
    {
        $this->update([
            'status'       => self::STATUS_FLAGGED,
            'flag_reason'  => $reason,
            'moderated_by' => $adminId,
            'moderated_at' => now(),
        ]);
    }

    public function rejectionCausesStrike(string $reason): bool
    {
        return in_array($reason, self::STRIKE_REASONS);
    }

    // ── Feature Helpers ───────────────────────────────────────────────────────

    /**
     * تكلفة التمييز — ترجع null للمدد غير المدعومة (آمنة للـ UI)
     */
    public static function featureCost(int $days): ?int
    {
        return self::FEATURE_COSTS[$days] ?? null;
    }

    /**
     * تكلفة التمييز — ترمي Exception للمدد غير المدعومة (للكود البرمجي)
     */
    public static function featureCostStrict(int $days): int
    {
        if (! array_key_exists($days, self::FEATURE_COSTS)) {
            throw new \InvalidArgumentException(__('server.dashboard.feature_unsupported_duration', [
                'days'   => $days,
                'values' => implode(', ', array_keys(self::FEATURE_COSTS)),
            ]));
        }

        return self::FEATURE_COSTS[$days];
    }

    /**
     * تمييز الإعلان بخصم نقاط من المعلن
     *
     * @throws \Exception لو النقاط مش كافية أو المدة غير مدعومة
     */
    public function featureWithPoints(int $days): void
    {
        $cost = self::featureCostStrict($days);
        $this->loadMissing('user');
        $user = $this->user;

        $entitlements = app(\App\Services\EntitlementService::class);

        $isNewFeaturedSlot = ! ($this->is_featured && $this->featured_until?->isFuture());

        if ($isNewFeaturedSlot && ! $entitlements->canUseFeature($user, \App\Services\EntitlementService::FEATURE_FEATURED_LISTINGS_LIMIT, $this)) {
            throw new \Exception(__('server.dashboard.feature_limit_plan'));
        }

        if (! $entitlements->canUseFeature($user, \App\Services\EntitlementService::FEATURE_MONTHLY_BOOST_LIMIT)) {
            throw new \Exception(__('server.dashboard.feature_limit_monthly'));
        }

        // ✅ تأكد من كفاية النقاط
        if (! $user->hasPoints($cost)) {
            throw new \Exception(
                __('server.dashboard.feature_insufficient_points', [
                    'cost'      => $cost,
                    'available' => $user->points,
                ])
            );
        }

        // ✅ خصم النقاط
        $user->decrement('points', $cost);

        // ✅ تسجيل معاملة النقاط (لو جدول point_transactions موجود)
        if (class_exists(\App\Models\PointTransaction::class)) {
            \App\Models\PointTransaction::create([
                'user_id'     => $user->id,
                'amount'      => -$cost,
                'type'        => 'feature_listing',
                'description' => "تمييز إعلان #{$this->id} لمدة {$days} أيام",
                'meta'        => json_encode(['listing_id' => $this->id, 'days' => $days]),
            ]);
        }

        // ✅ لو الإعلان مميز بالفعل — امتد من نهاية المدة الحالية
        $from = ($this->is_featured && $this->featured_until?->isFuture())
            ? $this->featured_until
            : now();

        $this->update([
            'is_featured'    => true,
            'featured_until' => $from->addDays($days),
        ]);

        $entitlements->recordUsage($user, \App\Services\EntitlementService::FEATURE_MONTHLY_BOOST_LIMIT);
    }

    /**
     * إلغاء التمييز
     */
    public function unfeature(): void
    {
        $this->update([
            'is_featured'    => false,
            'featured_until' => null,
        ]);
    }

    /**
     * هل الإعلان مميز حالياً؟
     */
    public function isCurrentlyFeatured(): bool
    {
        return $this->is_featured
            && $this->featured_until !== null
            && $this->featured_until->isFuture();
    }
public function toSearchableArray(): array
    {
        $this->loadMissing(['category', 'province', 'location']);

        return [
            'id'           => $this->id,
            'title'        => $this->title,
            'price'        => (float) $this->price,
            'status'       => $this->status,
            'category_id'  => $this->category_id,
            'car_brand_id' => $this->car_brand_id,
            'created_at'   => $this->created_at->timestamp,

            'category_name' => $this->category?->name_ar,
            'province_name' => $this->province?->name_ar,
            'location_name' => $this->location?->name_ar,

            '_geo' => ($this->location?->latitude && $this->location?->longitude) ? [
                'lat' => (float) $this->location->latitude,
                'lng' => (float) $this->location->longitude,
            ] : null,
        ];
    }
    }
