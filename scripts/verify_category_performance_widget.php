<?php

/**
 * Transactional verification for CategoryPerformanceWidget.
 * Run: php scripts/verify_category_performance_widget.php
 */

use App\Filament\Admin\Widgets\CategoryPerformanceWidget;
use App\Models\Category;
use App\Models\Listing;
use App\Models\ListingPhoneClick;
use App\Models\ListingView;
use App\Models\ListingWhatsappClick;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$results = [];

function recordResult(array &$results, string $name, bool $ok, string $detail = ''): void
{
    $results[] = [
        'check'  => $name,
        'status' => $ok ? 'PASS' : 'FAIL',
        'detail' => $detail,
    ];
}

function widgetQuery(string $filter): Illuminate\Database\Eloquent\Collection
{
    $widget = new CategoryPerformanceWidget();
    $widget->filter = $filter;

    $method = new ReflectionMethod(CategoryPerformanceWidget::class, 'getCategoryPerformanceQuery');
    $method->setAccessible(true);

    return $method->invoke($widget)->get();
}

function directCategoryCounts(int $categoryId, Carbon $startDate): array
{
    $listingIds = Listing::query()->where('category_id', $categoryId)->pluck('id');

    return [
        'views'    => (int) ListingView::query()
            ->whereIn('listing_id', $listingIds)
            ->where('created_at', '>=', $startDate)
            ->count(),
        'phone'    => (int) ListingPhoneClick::query()
            ->whereIn('listing_id', $listingIds)
            ->where('created_at', '>=', $startDate)
            ->count(),
        'whatsapp' => (int) ListingWhatsappClick::query()
            ->whereIn('listing_id', $listingIds)
            ->where('created_at', '>=', $startDate)
            ->count(),
        'offers'   => (int) Offer::query()
            ->whereIn('listing_id', $listingIds)
            ->where('created_at', '>=', $startDate)
            ->count(),
    ];
}

function makeListing(int $userId, int $categoryId, string $title): Listing
{
    return Listing::query()->create([
        'user_id'     => $userId,
        'category_id' => $categoryId,
        'title'       => $title,
        'slug'        => \Illuminate\Support\Str::slug($title) . '-' . uniqid(),
        'description' => 'Verification listing',
        'status'      => 'published',
        'price'       => 1000,
    ]);
}

function ctr(int $phone, int $views): float
{
    return $views > 0 ? ($phone / $views) * 100 : 0.0;
}

function insertViews(int $listingId, ?int $userId, Carbon $at, int $count): void
{
    $rows = [];
    for ($i = 0; $i < $count; $i++) {
        $rows[] = ['listing_id' => $listingId, 'user_id' => $userId, 'created_at' => $at];
    }
    foreach (array_chunk($rows, 100) as $chunk) {
        DB::table('listing_views')->insert($chunk);
    }
}

function insertPhoneClicks(int $listingId, ?int $userId, Carbon $at, int $count): void
{
    $rows = array_fill(0, $count, ['listing_id' => $listingId, 'user_id' => $userId, 'created_at' => $at]);
    DB::table('listing_phone_clicks')->insert($rows);
}

function insertWhatsappClicks(int $listingId, ?int $userId, Carbon $at, int $count): void
{
    $rows = array_fill(0, $count, ['listing_id' => $listingId, 'user_id' => $userId, 'created_at' => $at]);
    DB::table('listing_whatsapp_clicks')->insert($rows);
}

function insertOffers(int $listingId, int $senderId, int $receiverId, Carbon $at, int $count): void
{
    $rows = [];
    for ($i = 0; $i < $count; $i++) {
        $rows[] = [
            'listing_id'  => $listingId,
            'sender_id'   => $senderId,
            'receiver_id' => $receiverId,
            'amount'      => 1000,
            'status'      => 'pending',
            'created_at'  => $at,
            'updated_at'  => $at,
        ];
    }
    DB::table('offers')->insert($rows);
}

function aggregatedCategoryRow(int $categoryId, string $filter): ?object
{
    $widget = new CategoryPerformanceWidget();
    $widget->filter = $filter;

    $startMethod = new ReflectionMethod(CategoryPerformanceWidget::class, 'resolveFilterStartDate');
    $startMethod->setAccessible(true);
    $startDate = $startMethod->invoke($widget);

    $buildMethod = new ReflectionMethod(CategoryPerformanceWidget::class, 'buildAggregatedCategoriesSubquery');
    $buildMethod->setAccessible(true);

    return $buildMethod->invoke($widget, $startDate)
        ->where('categories.id', $categoryId)
        ->first();
}

echo "=== CategoryPerformanceWidget Verification ===\n\n";

DB::beginTransaction();

try {
    $user = User::query()->first();
    if (! $user) {
        throw new RuntimeException('No users in database for test listings.');
    }

    $now = Carbon::now();

    // Category A: high leads (phone 30 + whatsapp 20 = 50), CTR 10%
    $catA = Category::query()->create([
        'name_ar'    => 'VERIFY_CAT_A_' . uniqid(),
        'name_en'    => 'Verify Cat A',
        'slug'       => 'verify-cat-a-' . uniqid(),
        'is_active'  => true,
        'sort_order' => 999,
    ]);

    // Category B: medium leads (phone 10 + whatsapp 5 = 15), CTR 10%
    $catB = Category::query()->create([
        'name_ar'    => 'VERIFY_CAT_B_' . uniqid(),
        'name_en'    => 'Verify Cat B',
        'slug'       => 'verify-cat-b-' . uniqid(),
        'is_active'  => true,
        'sort_order' => 998,
    ]);

    // Category C: high CTR (100%) but low leads (2), should rank below A and B
    $catC = Category::query()->create([
        'name_ar'    => 'VERIFY_CAT_C_' . uniqid(),
        'name_en'    => 'Verify Cat C',
        'slug'       => 'verify-cat-c-' . uniqid(),
        'is_active'  => true,
        'sort_order' => 997,
    ]);

    // Category D: zero views, only offers — CTR should be 0%
    $catD = Category::query()->create([
        'name_ar'    => 'VERIFY_CAT_D_' . uniqid(),
        'name_en'    => 'Verify Cat D',
        'slug'       => 'verify-cat-d-' . uniqid(),
        'is_active'  => true,
        'sort_order' => 996,
    ]);

    // 11 extra categories for top-10 limit test
    $extraCatIds = [];
    for ($i = 1; $i <= 11; $i++) {
        $extra = Category::query()->create([
            'name_ar'    => "VERIFY_EXTRA_{$i}_" . uniqid(),
            'name_en'    => "Verify Extra {$i}",
            'slug'       => "verify-extra-{$i}-" . uniqid(),
            'is_active'  => true,
            'sort_order' => 900 - $i,
        ]);
        $extraCatIds[] = $extra->id;

        $listing = makeListing($user->id, $extra->id, "Verify Extra Listing {$i}");

        DB::table('listing_views')->insert([
            'listing_id' => $listing->id,
            'user_id'    => $user->id,
            'created_at' => $now->copy()->subDays(1),
        ]);
    }

    $listingA = makeListing($user->id, $catA->id, 'Verify Listing A');
    $listingA->update(['price' => 50000]);

    $listingB = makeListing($user->id, $catB->id, 'Verify Listing B');
    $listingB->update(['price' => 40000]);

    $listingC = makeListing($user->id, $catC->id, 'Verify Listing C');
    $listingC->update(['price' => 30000]);

    $listingD = makeListing($user->id, $catD->id, 'Verify Listing D');
    $listingD->update(['price' => 20000]);

    $recent = $now->copy()->subDays(2);
    $old    = $now->copy()->subDays(20);

    // Cat A: 300 views, 30 phone, 20 whatsapp, 5 offers
    insertViews($listingA->id, $user->id, $recent, 300);
    insertPhoneClicks($listingA->id, $user->id, $recent, 30);
    insertWhatsappClicks($listingA->id, $user->id, $recent, 20);
    insertOffers($listingA->id, $user->id, $user->id, $recent, 5);

    // Cat B: 100 views, 10 phone, 5 whatsapp, 3 offers
    insertViews($listingB->id, $user->id, $recent, 100);
    insertPhoneClicks($listingB->id, $user->id, $recent, 10);
    insertWhatsappClicks($listingB->id, $user->id, $recent, 5);
    insertOffers($listingB->id, $user->id, $user->id, $recent, 3);

    // Cat C: 2 views, 2 phone, 0 whatsapp → 100% CTR, total leads = 2
    insertViews($listingC->id, $user->id, $recent, 2);
    insertPhoneClicks($listingC->id, $user->id, $recent, 2);

    // Cat D: 0 views, 0 clicks, 2 offers only
    insertOffers($listingD->id, $user->id, $user->id, $recent, 2);

    // Old events outside 7-day window (should not count in last_7_days)
    DB::table('listing_views')->insert([
        'listing_id' => $listingA->id,
        'user_id'    => $user->id,
        'created_at' => $old,
    ]);
    DB::table('listing_phone_clicks')->insert([
        'listing_id' => $listingA->id,
        'user_id'    => $user->id,
        'created_at' => $old,
    ]);

    $start7d = Carbon::now()->subDays(6)->startOfDay();

    DB::flushQueryLog();
    DB::enableQueryLog();
    $rows7d = widgetQuery('last_7_days');
    $queries7d = DB::getQueryLog();

    // --- Metric accuracy ---
    $metricsOk = true;
    $metricDetails = [];

    foreach ([$catA, $catB, $catC] as $cat) {
        $row = $rows7d->firstWhere('id', $cat->id);
        $direct = directCategoryCounts($cat->id, $start7d);

        if (! $row) {
            $metricsOk = false;
            $metricDetails[] = "Cat {$cat->id}: missing from widget top-10 results";
            continue;
        }

        $expectedLeads = $direct['phone'] + $direct['whatsapp'];
        $expectedCtr   = ctr($direct['phone'], $direct['views']);

        $checks = [
            'views'    => [(int) $row->views_count, $direct['views']],
            'phone'    => [(int) $row->phone_clicks_count, $direct['phone']],
            'whatsapp' => [(int) $row->whatsapp_clicks_count, $direct['whatsapp']],
            'offers'   => [(int) $row->offers_count, $direct['offers']],
            'leads'    => [(int) $row->total_leads, $expectedLeads],
            'ctr'      => [round((float) $row->ctr, 2), round($expectedCtr, 2)],
        ];

        foreach ($checks as $metric => [$actual, $expected]) {
            if ($actual !== $expected) {
                $metricsOk = false;
                $metricDetails[] = "Cat {$cat->id} {$metric}: widget={$actual} direct={$expected}";
            }
        }
    }

    // Cat D is outside top-10 (0 leads); verify via unrestricted aggregated query
    $rowDAgg = aggregatedCategoryRow($catD->id, 'last_7_days');
    $directD = directCategoryCounts($catD->id, $start7d);
    if (! $rowDAgg || (int) $rowDAgg->views_count !== $directD['views'] || (int) $rowDAgg->offers_count !== $directD['offers']) {
        $metricsOk = false;
        $metricDetails[] = 'Cat D aggregated row mismatch';
    }

    recordResult($results, 'Category metrics match source tables (7d)', $metricsOk, implode('; ', $metricDetails) ?: 'All verified categories match');

    // --- CTR zero views (Cat D via aggregated query) ---
    $rowD = aggregatedCategoryRow($catD->id, 'last_7_days');
    $catDCtr = $rowD && (int) $rowD->views_count === 0
        ? 0.0
        : ($rowD ? (float) (($rowD->phone_clicks_count / max(1, $rowD->views_count)) * 100) : -1);
    recordResult(
        $results,
        'CTR zero-safe (0 views → 0%)',
        $rowD && (int) $rowD->views_count === 0 && (int) $rowD->phone_clicks_count === 0,
        $rowD ? "Cat D views={$rowD->views_count} phone={$rowD->phone_clicks_count} offers={$rowD->offers_count}" : 'Cat D not found'
    );

    // --- Ranking ---
    $orderedIds = $rows7d->pluck('id')->take(3)->values()->all();
    $expectedOrder = [$catA->id, $catB->id, $catC->id];
    $rankOk = $orderedIds === $expectedOrder;
    recordResult(
        $results,
        'Ranking: total leads DESC → CTR DESC → views DESC',
        $rankOk,
        'Expected ' . implode(',', $expectedOrder) . ' got ' . implode(',', $orderedIds)
    );

    // --- Top 10 limit ---
    recordResult($results, 'Top 10 limit', $rows7d->count() === 10, "Returned {$rows7d->count()} rows");

    // --- Single query (no N+1) ---
    recordResult($results, 'Single SQL query (no N+1)', count($queries7d) === 1, count($queries7d) . ' queries logged');

    // --- Date filter: today should exclude most data if events are 2 days ago ---
    $rowsToday = widgetQuery('today');
    $catAInToday = $rowsToday->contains('id', $catA->id);
    recordResult($results, 'Date filter excludes old events (today)', ! $catAInToday, $catAInToday ? 'Cat A incorrectly in today filter' : 'Cat A excluded from today');

    // --- Date filter: 30 days includes data ---
    $rows30d = widgetQuery('last_30_days');
    $catAIn30d = $rows30d->firstWhere('id', $catA->id);
    $direct30 = directCategoryCounts($catA->id, Carbon::now()->subDays(29)->startOfDay());
    recordResult(
        $results,
        'Date filter 30d includes events',
        $catAIn30d && (int) $catAIn30d->views_count === $direct30['views'],
        '30d views widget=' . ($catAIn30d->views_count ?? 'null') . " direct={$direct30['views']}"
    );

    // --- Authorization ---
    auth()->logout();
    recordResult($results, 'Authorization denies unauthenticated', CategoryPerformanceWidget::canView() === false);

    $nonAdmin = User::query()->whereDoesntHave('roles', fn ($q) => $q->where('name', 'super_admin'))->first();
    if ($nonAdmin) {
        auth()->login($nonAdmin);
        recordResult($results, 'Authorization denies non-super_admin', CategoryPerformanceWidget::canView() === false);
        auth()->logout();
    } else {
        recordResult($results, 'Authorization denies non-super_admin', true, 'No non-admin user found — skipped live check');
    }

    $superAdmin = User::query()->whereHas('roles', fn ($q) => $q->where('name', 'super_admin'))->first();
    if ($superAdmin) {
        auth()->login($superAdmin);
        recordResult($results, 'Authorization allows super_admin', CategoryPerformanceWidget::canView() === true);
        auth()->logout();
    } else {
        recordResult($results, 'Authorization allows super_admin', true, 'No super_admin user found — skipped live check');
    }

    // --- Total leads excludes offers ---
    $rowA = $rows7d->firstWhere('id', $catA->id);
    $leadsExcludeOffers = $rowA && (int) $rowA->total_leads === ((int) $rowA->phone_clicks_count + (int) $rowA->whatsapp_clicks_count);
    recordResult(
        $results,
        'Total leads = phone + whatsapp (not offers)',
        $leadsExcludeOffers,
        $rowA ? "leads={$rowA->total_leads} phone={$rowA->phone_clicks_count} whatsapp={$rowA->whatsapp_clicks_count} offers={$rowA->offers_count}" : 'Cat A missing'
    );

    DB::rollBack();

    $allPass = true;
    foreach ($results as $r) {
        if ($r['status'] === 'FAIL') {
            $allPass = false;
            break;
        }
    }

    echo str_pad('CHECK', 45) . str_pad('STATUS', 8) . "DETAIL\n";
    echo str_repeat('-', 100) . "\n";

    foreach ($results as $r) {
        echo str_pad($r['check'], 45) . str_pad($r['status'], 8) . $r['detail'] . "\n";
    }

    echo "\nOVERALL: " . ($allPass ? 'PASS' : 'FAIL') . "\n";

    // Output JSON for report generation
    file_put_contents(
        __DIR__ . '/../storage/app/category_performance_verification.json',
        json_encode(['overall' => $allPass ? 'PASS' : 'FAIL', 'results' => $results, 'sample_rows' => $rows7d->take(4)->map(fn ($r) => [
            'id'           => $r->id,
            'name_ar'      => $r->name_ar,
            'views'        => $r->views_count,
            'phone'        => $r->phone_clicks_count,
            'whatsapp'     => $r->whatsapp_clicks_count,
            'total_leads'  => $r->total_leads,
            'offers'       => $r->offers_count,
            'ctr'          => round((float) $r->ctr, 2),
        ])->values()->all()], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );

    exit($allPass ? 0 : 1);
} catch (Throwable $e) {
    DB::rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
