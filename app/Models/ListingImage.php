<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;

class ListingImage extends Model
{
    use HasFactory;

    protected $table = 'listing_images';

    protected $fillable = [
        'listing_id',
        'path',
        'disk',
        'mime_type',
        'size_bytes',
        'width',
        'height',
        'sort_order',
        'is_primary',
        'alt_text',
        'ai_labels',
        'moderation_status',
    ];

    protected function casts(): array
    {
        return [
            'is_primary'        => 'boolean',
            'sort_order'        => 'integer',
            'width'             => 'integer',
            'height'            => 'integer',
            'size_bytes'        => 'integer',
            'ai_labels'         => 'array',
        ];
    }

    // -----------------------------------------------------------------------
    // Accessors
    // -----------------------------------------------------------------------

    public function getUrlAttribute(): string
    {
        if (!$this->path) return '';

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk($this->disk ?? config('filesystems.default'));

        return $disk->url($this->path);
    }

    public function getIsApprovedAttribute(): bool
    {
        return $this->moderation_status === 'approved';
    }

    public function getSizeKbAttribute(): float
    {
        return round(($this->size_bytes ?? 0) / 1024, 1);
    }

    // -----------------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------------

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    // -----------------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------------

    public function scopeApproved(Builder $query): void
    {
        $query->where('moderation_status', 'approved');
    }

    // -----------------------------------------------------------------------
    // Domain Methods
    // -----------------------------------------------------------------------

    public function markAsPrimary(): void
    {
        static::where('listing_id', $this->listing_id)
              ->where('id', '!=', $this->id)
              ->update(['is_primary' => false]);

        $this->update(['is_primary' => true, 'sort_order' => 0]);
    }

    public function approve(): void
    {
        $this->update(['moderation_status' => 'approved']);
    }

    public function reject(): void
    {
        $this->update(['moderation_status' => 'rejected']);
    }

    // -----------------------------------------------------------------------
    // Boot — التنظيف التلقائي
    // -----------------------------------------------------------------------

    protected static function booted(): void
    {
        static::deleting(function (ListingImage $image) {
            if ($image->path) {
                Storage::disk($image->disk ?? config('filesystems.default'))->delete($image->path);
            }
        });
    }
}
