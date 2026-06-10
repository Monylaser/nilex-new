<?php

namespace App\Auth\ValueObjects;

use Illuminate\Support\Facades\Hash;

final class OtpCode
{
    public function __construct(
        public readonly string $plain,
        public readonly string $hash,
    ) {}

    public static function generate(int $length = 4): self
    {
        $max = (10 ** $length) - 1;
        $min = 10 ** ($length - 1);
        $plain = (string) random_int($min, $max);

        return new self($plain, Hash::make($plain));
    }

    public function matches(string $candidate): bool
    {
        return Hash::check($candidate, $this->hash);
    }

    public static function verify(string $candidate, string $storedHash): bool
    {
        return Hash::check($candidate, $storedHash);
    }
}
