<?php

namespace App\Auth\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeviceLimitService
{
    public function __construct(
        private DeviceFingerprintService $fingerprints,
    ) {}

    public function canCreateAccount(Request $request): bool
    {
        $max = config('auth-security.device_limit.max_accounts_per_device', 3);
        $cookieName = config('auth-security.device_limit.cookie_name', 'device_id');
        $deviceId = $request->cookie($cookieName);
        $fingerprint = $this->fingerprints->compute($request);
        $ip = $request->ip();

        return User::query()
            ->where(function ($query) use ($deviceId, $fingerprint, $ip) {
                if ($deviceId) {
                    $query->where('device_id', $deviceId);
                }
                $query->orWhere('fingerprint_hash', $fingerprint)
                    ->orWhere('ip_address', $ip);
            })
            ->count() < $max;
    }

    public function attachDeviceMetadata(User $user, Request $request, string $deviceId): void
    {
        $user->update([
            'ip_address' => $request->ip(),
            'device_id' => $deviceId,
            'fingerprint_hash' => $this->fingerprints->compute($request),
        ]);
    }

    /**
     * Atomic account-limit check to reduce registration race windows.
     */
    public function assertCanCreateAccount(Request $request): void
    {
        DB::transaction(function () use ($request) {
            if (! $this->canCreateAccount($request)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'contact' => __('server.auth.device_limit_detailed'),
                ]);
            }
        }, 3);
    }
}
