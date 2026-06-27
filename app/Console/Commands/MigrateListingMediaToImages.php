<?php

namespace App\Console\Commands;

use App\Models\Listing;
use Illuminate\Console\Command;
use Spatie\MediaLibrary\Conversions\FileManipulator;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * One-time data migration: move any Listing media stored under the legacy
 * 'listings' Spatie collection into the canonical 'images' collection.
 *
 * Why: user-submitted listings store images under 'images' (HomeController),
 * but admin-created listings historically uploaded to 'listings'. The admin
 * table/view read 'listings', so user listings showed no image (a moderation
 * blind spot). We standardize everything on 'images'; run this FIRST so no
 * existing admin-uploaded image is lost when the reads switch to 'images'.
 *
 * Safe to re-run (idempotent): once moved, there is nothing left under
 * 'listings' to migrate. Conversions (thumb/card/full_hd) are regenerated
 * because they are registered for all collections.
 */
class MigrateListingMediaToImages extends Command
{
    protected $signature = 'listings:migrate-media-collection {--dry-run : Only report what would change, without writing}';

    protected $description = "Move Listing media from the legacy 'listings' collection to the canonical 'images' collection";

    public function handle(FileManipulator $fileManipulator): int
    {
        $listingType = (new Listing())->getMorphClass();

        $query = Media::query()
            ->where('model_type', $listingType)
            ->where('collection_name', 'listings');

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info("No media found under the 'listings' collection — nothing to migrate.");

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->warn("[dry-run] {$total} media file(s) would be moved from 'listings' → 'images'.");

            return self::SUCCESS;
        }

        $moved = 0;

        $query->cursor()->each(function (Media $media) use ($fileManipulator, &$moved) {
            $media->collection_name = 'images';
            $media->save();

            // Regenerate derived files so thumb/card/full_hd exist for the moved media.
            $fileManipulator->createDerivedFiles($media);

            $moved++;
        });

        $this->info("Moved {$moved} media file(s) from 'listings' → 'images' and regenerated conversions.");

        return self::SUCCESS;
    }
}
