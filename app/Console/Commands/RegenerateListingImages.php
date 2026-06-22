<?php

namespace App\Console\Commands;

use App\Models\Listing;
use Illuminate\Console\Command;
use Spatie\MediaLibrary\Conversions\FileManipulator;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class RegenerateListingImages extends Command
{
    protected $signature = 'listings:regenerate-images {--listing= : Regenerate a single listing by ID}';

    protected $description = 'Regenerate listing media conversions (thumb, card, full_hd) so the Nilex watermark is applied to existing images';

    public function handle(FileManipulator $fileManipulator): int
    {
        $query = Listing::query()->has('media');

        if ($id = $this->option('listing')) {
            $query->whereKey($id);
        }

        $listings = $query->get();

        if ($listings->isEmpty()) {
            $this->info('No listings with media found — nothing to regenerate.');

            return self::SUCCESS;
        }

        $mediaCount = 0;

        $this->withProgressBar($listings, function (Listing $listing) use ($fileManipulator, &$mediaCount) {
            $listing->getMedia('images')->each(function (Media $media) use ($fileManipulator, &$mediaCount) {
                $fileManipulator->createDerivedFiles($media);
                $mediaCount++;
            });
        });

        $this->newLine(2);
        $this->info("Regenerated conversions for {$mediaCount} media file(s) across {$listings->count()} listing(s).");

        return self::SUCCESS;
    }
}
