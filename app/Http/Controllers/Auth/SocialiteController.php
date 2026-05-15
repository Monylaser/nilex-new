<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PointService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash; // 🟢 استدعاء الهاش مهم جداً
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Validation\ValidationException;

class SocialiteController extends Controller
{
    /**
     * توجيه المستخدم لصفحة تسجيل الدخول الخاصة بالشركة (جوجل، تيك توك، الخ)
     */
    public function redirect(string $provider): RedirectResponse
    {
        return Socialite::driver($provider)->redirect();
    }

    /**
     * استقبال المستخدم بعد ما يوافق على التسجيل
     */
    public function callback(string $provider): RedirectResponse
    {
        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            return redirect()->route('login')
                ->withErrors(['error' => 'حدث خطأ أثناء محاولة تسجيل الدخول عبر ' . ucfirst($provider)]);
        }

        // 1. هل اليوزر ده مسجل عندنا بالسوشيال ميديا قبل كده؟
        $user = User::where('provider_name', $provider)
                    ->where('provider_id', $socialUser->getId())
                    ->first();

        // 2. لو مش موجود، هندور عليه بالإيميل (يمكن سجل قبل كده بالإيميل العادي)
        if (!$user && $socialUser->getEmail()) {
            $user = User::where('email', $socialUser->getEmail())->first();

            // لو لقيناه، هنربط حسابه العادي بحساب السوشيال ميديا
            if ($user) {
                $user->update([
                    'provider_name' => $provider,
                    'provider_id'   => $socialUser->getId(),
                    'avatar'        => $user->avatar ?? $socialUser->getAvatar(),
                    'is_phone_verified' => true,
                ]);
            }
        }

        // 3. لو يوزر جديد لانج، هنكريتله حساب
        if (!$user) {
            $deviceId = request()->cookie('device_id');
            $ipAddress = request()->ip();

            // 🛡️ حماية الـ 3 حسابات كحد أقصى للجهاز الواحد
            $accountsCount = User::where(function($query) use ($deviceId, $ipAddress) {
                if ($deviceId) {
                    $query->where('device_id', $deviceId);
                }
                $query->orWhere('ip_address', $ipAddress);
            })->count();

            if ($accountsCount >= 3) {
                return redirect()->route('register')->withErrors(['contact' => 'عذراً، لقد وصلت للحد الأقصى لإنشاء الحسابات من هذا الجهاز.']);
            }

            $newCookie = false;
            if (!$deviceId) {
                $deviceId = (string) Str::uuid();
                $newCookie = true;
            }

            $user = User::create([
                'name'              => $socialUser->getName() ?? $socialUser->getNickname() ?? 'مستخدم نايلكس',
                'email'             => $socialUser->getEmail(),
                'password'          => Hash::make(Str::random(24)), // 🟢 باسورد عشوائي عشان الداتابيز متضربش إيرور
                'provider_name'     => $provider,
                'provider_id'       => $socialUser->getId(),
                'avatar'            => $socialUser->getAvatar(),
                'ip_address'        => $ipAddress,
                'device_id'         => $deviceId,
                'is_phone_verified' => true,
            ]);

            // 🎁 منح اليوزر 100 نقطة هدية التسجيل فوراً
            $pointService = new PointService();
            $pointService->credit($user, 100, 'هدية تسجيل الدخول عبر ' . ucfirst($provider) . ' 🎁');
            
            // زرع الكوكي الجديد لو مكانش موجود
            if ($newCookie) {
                Cookie::queue('device_id', $deviceId, 2628000);
            }
        }

        // 4. تسجيل الدخول والتوجيه للداشبورد
        Auth::login($user);

        return redirect()->intended(route('dashboard'))
            ->with('success', 'تم تسجيل الدخول بنجاح!');
    }
}