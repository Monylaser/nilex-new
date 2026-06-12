<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Listing;
use App\Models\Offer;

$listing = Listing::first();
$results = [];

if (! $listing) {
    echo json_encode(['overall' => 'FAIL', 'error' => 'No listings in database'], JSON_PRETTY_PRINT);
    exit(1);
}

$offersRelation = $listing->offers();
$relationOk = $offersRelation instanceof Illuminate\Database\Eloquent\Relations\HasMany;

$withCount = Listing::withCount('offers')->find($listing->id);
$directCount = Offer::query()->where('listing_id', $listing->id)->count();
$withCountOk = $withCount && (int) $withCount->offers_count === (int) $directCount;

$results[] = [
    'check' => 'Listing::first()?->offers() returns HasMany',
    'status' => $relationOk ? 'PASS' : 'FAIL',
    'detail' => $relationOk ? get_class($offersRelation) : 'Invalid relation type',
];

$results[] = [
    'check' => 'Listing::withCount(\'offers\') matches direct count',
    'status' => $withCountOk ? 'PASS' : 'FAIL',
    'detail' => "listing_id={$listing->id} withCount={$withCount?->offers_count} direct={$directCount}",
];

$overall = $relationOk && $withCountOk ? 'PASS' : 'FAIL';

$output = [
    'overall' => $overall,
    'listing_id' => $listing->id,
    'results' => $results,
];

file_put_contents(
    __DIR__ . '/../storage/app/listing_offers_relationship_verification.json',
    json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);

echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

exit($overall === 'PASS' ? 0 : 1);
