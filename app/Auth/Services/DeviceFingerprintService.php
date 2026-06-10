<?php

namespace App\Auth\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DeviceFingerprintService
{
    public function compute(Request $request): string
    {
        $components = [
            $request->userAgent() ?? '',
            $request->ip() ?? '',
            $request->cookie(config('auth-security.device_limit.cookie_name', 'device_id')) ?? '',
            $request->header('X-Timezone', ''),
            $request->header('X-Screen-Resolution', ''),
            $request->header('X-Platform', ''),
            $request->header('X-WebGL-Hash', ''),
        ];

        return hash('sha256', implode('|', $components));
    }

    public function resolveDeviceCookie(Request $request): array
    {
        $cookieName = config('auth-security.device_limit.cookie_name', 'device_id');
        $deviceId = $request->cookie($cookieName);

        if ($deviceId && Str::isUuid($deviceId)) {
            return ['id' => $deviceId, 'new' => false];
        }

        return ['id' => (string) Str::uuid(), 'new' => true];
    }
}
