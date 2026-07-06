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
                // is_phone_verified حقل حسّاس خارج $fillable — يُكتب عبر forceFill.
                $user->forceFill([
                    'provider_name' => $provider,
                    'provider_id' => $socialUser->getId(),
                    'avatar' => $user->avatar ?? $socialUser->getAvatar(),
                    'is_phone_verified' => true,
                ])->save();
            }
        }

        if (! $user) {
            if (! $this->deviceLimit->canCreateAccount(request())) {
                return redirect()->route('register')
                    ->withErrors(['contact' => __('server.auth.device_limit')]);
            }

            $device = $this->fingerprints->resolveDeviceCookie(request());

            $user = DB::transaction(function () use ($socialUser, $provider, $device) {
                $newUser = User::create([
                    'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? 'مستخدم نايلكس',
                    'email' => $socialUser->getEmail(),
                    'password' => Hash::make(Str::random(24)),
                    'provider_name' => $provider,
                    'provider_id' => $socialUser->getId(),
                    'avatar' => $socialUser->getAvatar(),
                    'ip_address' => request()->ip(),
                    'device_id' => $device['id'],
                    'fingerprint_hash' => $this->fingerprints->compute(request()),
                ]);

                // is_phone_verified حقل حسّاس خارج $fillable — يُكتب عبر forceFill.
                $newUser->forceFill(['is_phone_verified' => true])->save();

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
