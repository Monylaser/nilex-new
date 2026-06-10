<?php

/**
 * Nilex Platform — Pest Feature Tests
 *
 * Epic 1 Phase 8: Fraud & Risk Management — Auto-Flagging Observer
 *
 * Covers:
 *   - Listings containing fraud/spam keywords are auto-flagged on create.
 *   - Listings containing fraud/spam keywords are auto-flagged on update.
 *   - Clean listings are NOT flagged.
 *   - Every keyword in the FRAUD_KEYWORDS list is caught.
 *   - flag_reason stores the matched keyword.
 *   - Status is forced to 'pending' when a match is found.
 */

use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use App\Observers\ListingObserver;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ─────────────────────────────────────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────────────────────────────────────

function makeCategory(): Category
{
    return Category::create([
        'name_ar'   => 'تجربة',
        'name_en'   => 'test',
        'slug'      => 'test-' . uniqid(),
        'is_active' => true,
    ]);
}

function makeUser(): User
{
    return User::factory()->create();
}

function listingData(string $title = 'إعلان عادي', string $description = 'وصف نظيف'): array
{
    return [
        'title'       => $title,
        'description' => $description,
        'price'       => 100.00,
        'status'      => 'published', // intentionally set to published to prove observer overrides it
        'slug'        => 'listing-' . uniqid(),
    ];
}

// ─────────────────────────────────────────────────────────────────────────────
// Group: Observer — Creating
// ─────────────────────────────────────────────────────────────────────────────

describe('ListingObserver — creating', function () {

    beforeEach(function () {
        $this->user     = makeUser();
        $this->category = makeCategory();
    });

    it('flags a listing whose title contains a fraud keyword', function () {
        $listing = Listing::create(listingData('موبايل للبيع نصب') + [
            'user_id'     => $this->user->id,
            'category_id' => $this->category->id,
        ]);

        expect($listing->is_flagged)->toBeTrue()
            ->and($listing->status)->toBe(Listing::STATUS_PENDING)
            ->and($listing->flag_reason)->toContain('نصب');
    });

    it('flags a listing whose description contains a fraud keyword', function () {
        $listing = Listing::create(listingData('موبايل للبيع', 'هذا الإعلان احتيال واضح') + [
            'user_id'     => $this->user->id,
            'category_id' => $this->category->id,
        ]);

        expect($listing->is_flagged)->toBeTrue()
            ->and($listing->flag_reason)->toContain('احتيال');
    });

    it('does NOT flag a clean listing', function () {
        $listing = Listing::create(listingData('آيفون 15 للبيع', 'حالة ممتازة بضمان') + [
            'user_id'     => $this->user->id,
            'category_id' => $this->category->id,
        ]);

        // Reload from DB to get column defaults
        $fresh = $listing->fresh();
        expect($fresh->is_flagged)->toBeFalse()
            ->and($fresh->flag_reason)->toBeNull();
    });

    it('forces status to pending when a fraud keyword is detected, even if published was requested', function () {
        $listing = Listing::create([
            'title'       => 'ربح سريع من البيت',
            'description' => 'فرصة للجميع',
            'price'       => 50.00,
            'status'      => 'published',
            'slug'        => 'listing-' . uniqid(),
            'user_id'     => $this->user->id,
            'category_id' => $this->category->id,
        ]);

        expect($listing->status)->toBe(Listing::STATUS_PENDING);
    });

    it('stores the matched keyword in flag_reason', function () {
        $listing = Listing::create(listingData('هاتف مسروق للبيع') + [
            'user_id'     => $this->user->id,
            'category_id' => $this->category->id,
        ]);

        expect($listing->flag_reason)->toContain('مسروق');
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Group: Observer — Updating
// ─────────────────────────────────────────────────────────────────────────────

describe('ListingObserver — updating', function () {

    beforeEach(function () {
        $this->user     = makeUser();
        $this->category = makeCategory();

        $this->listing = Listing::create(listingData() + [
            'user_id'     => $this->user->id,
            'category_id' => $this->category->id,
        ]);
    });

    it('flags a previously clean listing when a fraud keyword is added in an update', function () {
        // Reload from DB to confirm the default is false, not null
        expect($this->listing->fresh()->is_flagged)->toBeFalse();

        $this->listing->update(['title' => 'سيارة مضروبة للبيع']);

        expect($this->listing->fresh()->is_flagged)->toBeTrue()
            ->and($this->listing->fresh()->flag_reason)->toContain('مضروب');
    });

    it('does NOT flag a listing updated with clean content', function () {
        $this->listing->update(['title' => 'موبايل جديد بسعر مناسب']);

        expect($this->listing->fresh()->is_flagged)->toBeFalse();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Group: Full keyword coverage
// ─────────────────────────────────────────────────────────────────────────────

describe('ListingObserver — every fraud keyword is caught', function () {

    beforeEach(function () {
        $this->user     = makeUser();
        $this->category = makeCategory();
    });

    $keywords = ListingObserver::FRAUD_KEYWORDS;

    foreach ($keywords as $keyword) {
        it("flags a listing containing the keyword: {$keyword}", function () use ($keyword) {
            $listing = Listing::create([
                'title'       => "إعلان يحتوي على {$keyword}",
                'description' => 'وصف اختبار',
                'price'       => 100.00,
                'status'      => 'pending',
                'slug'        => 'listing-' . uniqid(),
                'user_id'     => $this->user->id,
                'category_id' => $this->category->id,
            ]);

            expect($listing->is_flagged)->toBeTrue()
                ->and($listing->flag_reason)->toContain($keyword);
        });
    }
});

// ─────────────────────────────────────────────────────────────────────────────
// Group: Observer unit — pure logic without DB
// ─────────────────────────────────────────────────────────────────────────────

describe('ListingObserver — pure unit (no DB)', function () {

    it('detects a keyword embedded in the middle of a sentence', function () {
        $observer = new ListingObserver();
        $listing  = new Listing([
            'title'       => 'مرحبا هذا وهمي للاختبار',
            'description' => 'لا يوجد شيء',
            'status'      => 'published',
        ]);

        // Call the internal logic via creating()
        $observer->creating($listing);

        expect($listing->is_flagged)->toBeTrue()
            ->and($listing->status)->toBe(Listing::STATUS_PENDING);
    });

    it('does not flag when no keywords present', function () {
        $observer = new ListingObserver();
        $listing  = new Listing([
            'title'       => 'كاميرا سوني جديدة',
            'description' => 'بحالة ممتازة مع جميع الملحقات',
            'status'      => 'published',
        ]);

        $observer->creating($listing);

        expect($listing->is_flagged)->toBeNull(); // property not set = not flagged
    });

    it('only flags once even if multiple keywords are present', function () {
        $observer = new ListingObserver();
        $listing  = new Listing([
            'title'       => 'نصب واحتيال ومزور',
            'description' => 'وصف',
            'status'      => 'published',
        ]);

        $observer->creating($listing);

        // flag_reason should contain the FIRST matched keyword only
        expect($listing->flag_reason)->toContain('نصب')
            ->and($listing->is_flagged)->toBeTrue();
    });
});
