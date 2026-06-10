<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SeoTemplate extends Model
{
    protected $fillable = [
        'target_model',
        'target_id',
        'meta_title',
        'meta_description',
        'keywords',
        'og_image',
        'canonical_url',
        'schema_markup',
    ];

    protected $casts = [
        'schema_markup' => 'array',
    ];

    // ── Relationship ──────────────────────────────────────────────────────────

    public function target(): MorphTo
    {
        return $this->morphTo('target', 'target_model', 'target_id');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public static function supportedModels(): array
    {
        return [
            Category::class => 'تصنيف (Category)',
            Location::class => 'موقع (Location)',
        ];
    }

    /**
     * Find or create the SEO template for a given model instance.
     */
    public static function forModel(Model $model): ?self
    {
        return static::query()
            ->where('target_model', get_class($model))
            ->where('target_id', $model->getKey())
            ->first();
    }
}
