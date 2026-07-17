<?php

namespace Tests\Feature\Security;

use App\Livewire\Frontend\BuyerPurchases;
use App\Models\Category;
use App\Models\Listing;
use App\Models\SaleConfirmation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Audit #3 / #10 — missing throttles on sensitive routes.
 * Pattern mirrors OtpResendHardeningTest: N allowed attempts then assert 429.
 */
class SensitiveRouteThrottleTest extends TestCase
{
    use RefreshDatabase;

    private function verifiedUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'is_phone_verified' => true,
            'email_verified_at' => now(),
        ], $overrides));
    }

    #[Test]
    public function listing_store_rejects_after_twenty_requests_per_hour(): void
    {
        $user = $this->verifiedUser();

        foreach (range(1, 20) as $i) {
            $this->actingAs($user)
                ->postJson(route('listings.store'), [])
                ->assertStatus(422);
        }

        $this->actingAs($user)
            ->postJson(route('listings.store'), [])
            ->assertStatus(429);
    }

    #[Test]
    public function listing_update_rejects_after_twenty_requests_per_hour(): void
    {
        $user = $this->verifiedUser();
        $category = Category::create([
            'name_ar' => 'إلكترونيات',
            'name_en' => 'Electronics',
            'slug' => 'electronics-throttle-'.uniqid(),
            'is_active' => true,
        ]);
        $listing = Listing::create([
            'title' => 'إعلان اختبار',
            'slug' => 'listing-throttle-'.uniqid(),
            'description' => 'وصف كافٍ للاختبار هنا.',
            'price' => 1000,
            'category_id' => $category->id,
            'user_id' => $user->id,
            'status' => Listing::STATUS_PUBLISHED,
        ]);

        foreach (range(1, 20) as $i) {
            $this->actingAs($user)
                ->putJson(route('listings.update', $listing), [])
                ->assertStatus(422);
        }

        $this->actingAs($user)
            ->putJson(route('listings.update', $listing), [])
            ->assertStatus(429);
    }

    #[Test]
    public function ai_generate_rejects_fourth_request_per_minute(): void
    {
        config(['services.gemini.key' => 'AIza-test-fake-key-for-mocking']);

        $fakeJson = json_encode([
            'title' => 'عنوان',
            'description' => 'وصف تجريبي',
            'suggested_price' => '100',
            'category' => 'سيارات',
        ], JSON_UNESCAPED_UNICODE);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [['text' => $fakeJson]],
                    ],
                ]],
            ], 200),
        ]);

        $user = $this->verifiedUser();

        foreach (range(1, 3) as $i) {
            $this->actingAs($user)
                ->postJson(route('listings.ai-generate'), ['prompt' => 'سيارة مستعملة بحالة جيدة'])
                ->assertSuccessful();
        }

        $this->actingAs($user)
            ->postJson(route('listings.ai-generate'), ['prompt' => 'سيارة مستعملة بحالة جيدة'])
            ->assertStatus(429);
    }

    #[Test]
    public function password_email_rejects_sixth_request_per_minute(): void
    {
        Notification::fake();

        foreach (range(1, 5) as $i) {
            $this->post(route('password.email'), [
                'email' => "user{$i}@example.com",
            ]);
        }

        $this->post(route('password.email'), [
            'email' => 'extra@example.com',
        ])->assertStatus(429);
    }

    #[Test]
    public function password_store_rejects_sixth_request_per_minute(): void
    {
        foreach (range(1, 5) as $i) {
            $this->post(route('password.store'), [
                'token' => 'invalid-token',
                'email' => "user{$i}@example.com",
                'password' => 'Password1!',
                'password_confirmation' => 'Password1!',
            ]);
        }

        $this->post(route('password.store'), [
            'token' => 'invalid-token',
            'email' => 'extra@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
        ])->assertStatus(429);
    }

    #[Test]
    public function offer_rejects_eleventh_successful_offer_per_minute(): void
    {
        $seller = $this->verifiedUser();
        $buyer = $this->verifiedUser();
        $category = Category::create([
            'name_ar' => 'إلكترونيات',
            'name_en' => 'Electronics',
            'slug' => 'electronics-offer-'.uniqid(),
            'is_active' => true,
        ]);

        foreach (range(1, 10) as $i) {
            $listing = Listing::create([
                'title' => "إعلان عرض {$i}",
                'slug' => 'offer-listing-'.uniqid(),
                'description' => 'وصف كافٍ للاختبار هنا.',
                'price' => 1000 + $i,
                'category_id' => $category->id,
                'user_id' => $seller->id,
                'status' => Listing::STATUS_PUBLISHED,
            ]);

            $this->actingAs($buyer)
                ->postJson(route('listings.offer', $listing), [
                    'amount' => 900,
                    'message' => 'عرض',
                ])
                ->assertSuccessful();
        }

        $listing = Listing::create([
            'title' => 'إعلان عرض إضافي',
            'slug' => 'offer-listing-extra-'.uniqid(),
            'description' => 'وصف كافٍ للاختبار هنا.',
            'price' => 2000,
            'category_id' => $category->id,
            'user_id' => $seller->id,
            'status' => Listing::STATUS_PUBLISHED,
        ]);

        $this->actingAs($buyer)
            ->postJson(route('listings.offer', $listing), [
                'amount' => 900,
                'message' => 'عرض',
            ])
            ->assertStatus(429)
            ->assertJsonStructure(['error']);
    }

    #[Test]
    public function phone_reveal_rejects_sixteenth_request_per_minute(): void
    {
        $viewer = $this->verifiedUser();
        $seller = $this->verifiedUser(['phone' => '01012345678']);
        $category = Category::create([
            'name_ar' => 'إلكترونيات',
            'name_en' => 'Electronics',
            'slug' => 'electronics-phone-'.uniqid(),
            'is_active' => true,
        ]);
        $listing = Listing::create([
            'title' => 'إعلان هاتف',
            'slug' => 'phone-listing-'.uniqid(),
            'description' => 'وصف كافٍ للاختبار هنا.',
            'price' => 1000,
            'category_id' => $category->id,
            'user_id' => $seller->id,
            'status' => Listing::STATUS_PUBLISHED,
            'phone' => '01012345678',
        ]);

        foreach (range(1, 15) as $i) {
            $this->actingAs($viewer)
                ->postJson(route('listings.reveal-phone', $listing))
                ->assertSuccessful();
        }

        $this->actingAs($viewer)
            ->postJson(route('listings.reveal-phone', $listing))
            ->assertStatus(429);
    }

    #[Test]
    public function review_submit_rejects_sixth_attempt_per_minute(): void
    {
        $seller = $this->verifiedUser();
        $buyer = $this->verifiedUser();
        $category = Category::create([
            'name_ar' => 'إلكترونيات',
            'name_en' => 'Electronics',
            'slug' => 'electronics-review-'.uniqid(),
            'is_active' => true,
        ]);
        $listing = Listing::create([
            'title' => 'إعلان تقييم',
            'slug' => 'review-listing-'.uniqid(),
            'description' => 'وصف كافٍ للاختبار هنا.',
            'price' => 1000,
            'category_id' => $category->id,
            'user_id' => $seller->id,
            'status' => Listing::STATUS_PUBLISHED,
        ]);
        $sale = SaleConfirmation::create([
            'listing_id' => $listing->id,
            'seller_id' => $seller->id,
            'buyer_id' => $buyer->id,
            'status' => SaleConfirmation::STATUS_CONFIRMED,
            'seller_confirmed_at' => now(),
            'buyer_confirmed_at' => now(),
        ]);

        foreach (range(1, 5) as $i) {
            RateLimiter::hit('reviews|'.$buyer->id, 60);
        }

        Livewire::actingAs($buyer)
            ->test(BuyerPurchases::class)
            ->call('setRating', $sale->id, 5)
            ->call('submitReview', $sale->id)
            ->assertStatus(429);
    }
}
