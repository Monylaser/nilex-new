<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class LegalPage extends Model
{
    use HasTranslations;

    /** @var list<string> Spatie translatable attributes */
    public array $translatable = ['title', 'content'];

    protected $fillable = [
        'title',
        'slug',
        'content',
        'is_active',
        'seo_title',
        'seo_description',
        'meta_keywords',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ── Auto-generate slug on create ─────────────────────────────────────────

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $page): void {
            if (empty($page->slug)) {
                // $page->title returns a locale-resolved string via HasTranslations
                $page->slug = Str::slug($page->title);
            }
        });
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function getEffectiveSeoTitle(): string
    {
        return $this->seo_title ?: $this->title . ' | نايلكس';
    }

    public function getEffectiveSeoDescription(): string
    {
        return $this->seo_description ?: Str::limit(strip_tags($this->content), 160);
    }
}
