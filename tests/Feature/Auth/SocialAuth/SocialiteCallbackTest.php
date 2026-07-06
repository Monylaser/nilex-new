<?php

namespace Tests\Feature\Auth\SocialAuth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\User as SocialiteUserContract;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SocialiteCallbackTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_marks_social_users_as_email_verified_not_phone_verified(): void
    {
        $socialUser = Mockery::mock(SocialiteUserContract::class);
        $socialUser->shouldReceive('getId')->andReturn('social-123');
        $socialUser->shouldReceive('getEmail')->andReturn('social@example.com');
        $socialUser->shouldReceive('getName')->andReturn('Social User');
        $socialUser->shouldReceive('getNickname')->andReturn(null);
        $socialUser->shouldReceive('getAvatar')->andReturn(null);

        $provider = Mockery::mock(\Laravel\Socialite\Contracts\Provider::class);
        $provider->shouldReceive('user')->andReturn($socialUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $this->get('/auth/google/callback')->assertRedirect();

        $user = User::where('email', 'social@example.com')->first();

        // السوشيال يوثّق الإيميل فقط — بوابة OTP تقبل أي القناتين فيمرّ المستخدم،
        // لكن الهاتف يظل غير موثّق حتى يمرّ بمسار OTP الخاص به.
        $this->assertNotNull($user);
        $this->assertNotNull($user->email_verified_at);
        $this->assertFalse((bool) $user->is_phone_verified);
    }
}
