<?php

/**
 * Pre-launch security: JSON-LD on listing detail must not allow stored XSS
 * via a crafted listing title that breaks out of <script type="application/ld+json">.
 */

use App\Models\Category;
use App\Models\Listing;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('does not break out of JSON-LD script when the listing title contains a script payload', function () {
    $seller = User::factory()->create(['is_phone_verified' => true]);

    $category = Category::create([
        'name_ar'   => 'إلكترونيات',
        'name_en'   => 'Electronics',
        'slug'      => 'electronics-jsonld-xss-'.uniqid(),
        'is_active' => true,
    ]);

    $payload = '</script><script>alert(1)</script>';

    $listing = Listing::create([
        'title'       => $payload,
        'slug'        => 'jsonld-xss-'.uniqid(),
        'description' => 'Safe description for JSON-LD XSS coverage.',
        'price'       => 1_500,
        'category_id' => $category->id,
        'user_id'     => $seller->id,
        'status'      => Listing::STATUS_PUBLISHED,
    ]);

    $response = $this->get(route('listings.show', $listing));

    $response->assertOk();
    $response->assertSee('application/ld+json', false);

    // Raw HTML breakout must never appear unescaped in the response body.
    $html = $response->getContent();
    expect($html)->not->toContain('</script><script>alert(1)</script>');

    // Blade @json HEX-encodes angle brackets (JSON_HEX_TAG).
    expect($html)->toContain('\u003C\/script\u003E')
        ->and($html)->toContain('\u003Cscript\u003E');
});
