<?php

namespace Tests\Unit\Auth;

use App\Auth\ValueObjects\OtpCode;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OtpCodeTest extends TestCase
{
    #[Test]
    public function it_generates_a_hashed_otp_that_verifies_correctly(): void
    {
        $otp = OtpCode::generate(4);

        $this->assertSame(4, strlen($otp->plain));
        $this->assertTrue(OtpCode::verify($otp->plain, $otp->hash));
        $this->assertFalse(OtpCode::verify('0000', $otp->hash));
    }
}
