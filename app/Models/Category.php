<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_ar',
        'name_en',
        'slug',
        'icon',
        'views_count',
        'color',
        'sort_order',
        'is_active',
        'parent_id',
        'custom_fields_schema',
    ];

    protected function casts(): array
    {
        return [
            'is_active'            => 'boolean',
            'sort_order'           => 'integer',
            'views_count'          => 'integer', // أضف هذا السطر
            'custom_fields_schema' => 'array', // Automatically JSON encode/decode
        ];
    }

    // -----------------------------------------------------------------------
    // Accessors
    // -----------------------------------------------------------------------

    /**
     * Return the appropriate name based on the current app locale.
     */
    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name_en;
    }

    public function getIsRootAttribute(): bool
    {
        return $this->parent_id === null;
    }

    // -----------------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------------

    /** Direct parent category */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /** Direct children only */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')
                    ->orderBy('sort_order');
    }

    /** Recursive children (all descendants) — use carefully on large trees */
    public function allChildren(): HasMany
    {
        return $this->children()->with('allChildren');
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    // -----------------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------------

    public function scopeActive($query): void
    {
        $query->where('is_active', true);
    }

    public function scopeRoots($query): void
    {
        $query->whereNull('parent_id');
    }

    public function scopeOrdered($query): void
    {
        $query->orderBy('sort_order');
    }

    // -----------------------------------------------------------------------
    // Boot
    // -----------------------------------------------------------------------

    protected static function booted(): void
    {
        // Auto-generate slug from English name if not provided
        static::creating(function (Category $category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name_en);
            }
        });
    }
}
