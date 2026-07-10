<?php

namespace App\Http\Controllers\Auth;

use App\Auth\Services\DeviceFingerprintService;
use App\Auth\Services\DeviceLimitService;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PointService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    public function __construct(
        private DeviceLimitService $deviceLimit,
        private DeviceFingerprintService $fingerprints,
        private PointService $pointService,
    ) {}

    public function redirect(string $provider): RedirectResponse
    {
        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider): RedirectResponse
    {
        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            return redirect()->route('login')
                ->withErrors(['error' => __('server.auth.social_error', ['provider' => ucfirst($provider)])]);
        }

        $user = User::where('provider_name', $provider)
            ->where('provider_id', $socialUser->getId())
            ->first();

        if (! $user && $socialUser->getEmail()) {
            $user = User::where('email', $socialUser->getEmail())->first();

            if ($user) {
                // السوشيال يثبت ملكية الإيميل فقط — لا نلمس is_phone_verified إطلاقاً.
                // email_verified_at خارج $fillable فيُكتب عبر forceFill.
                $user->forceFill([
                    'provider_name' => $provider,
                    'provider_id' => $socialUser->getId(),
                    'avatar' => $user->avatar ?? $socialUser->getAvatar(),
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ])->save();
            }
        }

        if (! $user) {
            if (! $this->deviceLimit->canCreateAccount(request())) {
                return redirect()->route('register')
                    ->withErrors(['contact' => __('server.auth.device_limit')]);
            }

            $device = $this->fingerprints->resolveDeviceCookie(request());

            // L2 (deferred): Social signup does not attribute ?ref= campaign referrals.
            // Product decision pending — mirror RegisteredUserController::store() when approved.
            $user = DB::transaction(function () use ($socialUser, $provider, $device) {
                $newUser = User::create([
                    'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? __('server.auth.default_social_name'),
                    'email' => $socialUser->getEmail(),
                    'password' => Hash::make(Str::random(24)),
                    'provider_name' => $provider,
                    'provider_id' => $socialUser->getId(),
                    'avatar' => $socialUser->getAvatar(),
                    'ip_address' => request()->ip(),
                    'device_id' => $device['id'],
                    'fingerprint_hash' => $this->fingerprints->compute(request()),
                ]);

                // السوشيال يوثّق الإيميل فقط (وليس الهاتف): نضبط email_verified_at
                // ويظل توثيق الهاتف مساراً منفصلاً (OTP) لو أراد مكافأة الهاتف.
                // الحقل خارج $fillable فيُكتب عبر forceFill.
                $newUser->forceFill(['email_verified_at' => now()])->save();

                return $newUser;
            });

            $this->pointService->credit($user, (int) config('pricing.registration_welcome_points', 20), 'هدية تسجيل الدخول عبر '.ucfirst($provider).' 🎁');

            if ($device['new']) {
                Cookie::queue(
                    config('auth-security.device_limit.cookie_name', 'device_id'),
                    $device['id'],
                    config('auth-security.device_limit.cookie_minutes', 2628000),
                    '/',
                    null,
                    request()->secure(),
                    true,
                    false,
                    'lax'
                );
            }
        }

        Auth::login($user);
        // منع تثبيت الجلسة (session fixation): جدّد مُعرّف الجلسة بعد تسجيل الدخول.
        request()->session()->regenerate();

        return redirect()->intended(route('dashboard'))
            ->with('success', __('server.auth.login_success'));
    }
}
