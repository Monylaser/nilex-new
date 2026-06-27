<?php

namespace App\Http\Controllers\Auth;

use App\Auth\Services\DeviceFingerprintService;
use App\Auth\Services\DeviceLimitService;
use App\Auth\Services\OtpService;
use App\Http\Controllers\Controller;
use App\Models\CampaignLink;
use App\Models\PointTransaction;
use App\Models\User;
use App\Services\PointService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function __construct(
        private OtpService $otpService,
        private DeviceLimitService $deviceLimit,
        private DeviceFingerprintService $fingerprints,
        private PointService $pointService,
    ) {}

    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        // ── 1. Validate ──────────────────────────────────────────────
        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'contact'  => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // ── 2. Detect contact type ───────────────────────────────────
        $isEmail = (bool) filter_var($request->contact, FILTER_VALIDATE_EMAIL);
        $isPhone = (bool) preg_match('/^01[0-9]{9}$/', $request->contact);

        if (! $isEmail && ! $isPhone) {
            throw ValidationException::withMessages([
                'contact' => __('server.auth.contact_invalid'),
            ]);
        }

        // ── 3. Uniqueness check ──────────────────────────────────────
        if ($isEmail && User::where('email', $request->contact)->exists()) {
            throw ValidationException::withMessages([
                'contact' => __('server.auth.email_taken'),
            ]);
        }

        if ($isPhone && User::where('phone', $request->contact)->exists()) {
            throw ValidationException::withMessages([
                'contact' => __('server.auth.phone_taken'),
            ]);
        }

        // ── 4. Device limit check ────────────────────────────────────
        $this->deviceLimit->assertCanCreateAccount($request);

        // ── 5. Resolve device fingerprint ────────────────────────────
        $device = $this->fingerprints->resolveDeviceCookie($request);

        // ── 6. Create user ───────────────────────────────────────────
        $user = User::create([
            'name'              => $request->name,
            'email'             => $isEmail ? $request->contact : null,
            'phone'             => $isPhone ? $request->contact : null,
            'password'          => Hash::make($request->password),
            'ip_address'        => $request->ip(),
            'device_id'         => $device['id'],
            'fingerprint_hash'  => $this->fingerprints->compute($request),
            'is_phone_verified' => false,
        ]);

        // ── 7. Issue OTP ─────────────────────────────────────────────
        $this->otpService->issue($user, $isEmail);

        // ── 8. Welcome points ────────────────────────────────────────
        $this->pointService->credit(
            $user,
            50,
            'هدية ترحيبية بمناسبة الانضمام لمنصة نايلكس 🎁'
        );

        // ── 9. Campaign referral reward ──────────────────────────────
        $campaignCode = session('campaign_code');
        if ($campaignCode) {
            $campaign = CampaignLink::where('code', $campaignCode)->first();
            if ($campaign && $campaign->isValid()) {
                $user->increment('points_balance', $campaign->points_reward);
                PointTransaction::create([
                    'user_id'         => $user->id,
                    'amount'          => $campaign->points_reward,
                    'current_balance' => $user->fresh()->points_balance,
                    'description'     => "مكافأة رابط الإحالة: {$campaign->code}",
                ]);
                $campaign->increment('used_count');
                session()->forget('campaign_code');
            }
        }

        // ── 10. Fire event + login ───────────────────────────────────
        event(new Registered($user));
        Auth::login($user);

        // ── 11. Redirect with device cookie if new ───────────────────
        $response = redirect()->route('otp.notice');

        if ($device['new']) {
            $response->cookie(
                config('auth-security.device_limit.cookie_name', 'device_id'),
                $device['id'],
                config('auth-security.device_limit.cookie_minutes', 2628000),
                '/',
                null,
                $request->secure(),
                true,
                false,
                'lax'
            );
        }

        return $response;
    }
}