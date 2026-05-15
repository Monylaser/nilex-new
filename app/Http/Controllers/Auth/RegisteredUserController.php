<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SmsService;
use App\Services\PointService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Mail; // 🟢 استدعاء الميل
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * عرض صفحة التسجيل
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * معالجة طلب التسجيل الجديد (إيميل أو موبايل)
     */
    public function store(Request $request): RedirectResponse
    {
        // 1. التحقق من البيانات
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact' => ['required', 'string'], // 🟢 حقل واحد يقبل إيميل أو رقم
            'password' => ['required', Rules\Password::defaults()], // شيلنا confirmed عشان تناسب واجهة التريند
        ]);

        // 2. تحليل حقل الـ contact 
        $isEmail = filter_var($request->contact, FILTER_VALIDATE_EMAIL);
        $isPhone = preg_match('/^[0-9]+$/', $request->contact);

        if (!$isEmail && !$isPhone) {
            throw ValidationException::withMessages([
                'contact' => 'الرجاء إدخال بريد إلكتروني صحيح أو رقم هاتف صالح.',
            ]);
        }

        // 3. التحقق من عدم تكرار الحساب
        if ($isEmail && User::where('email', $request->contact)->exists()) {
            throw ValidationException::withMessages(['contact' => 'هذا البريد الإلكتروني مسجل بالفعل.']);
        }
        if ($isPhone && User::where('phone', $request->contact)->exists()) {
            throw ValidationException::withMessages(['contact' => 'رقم الهاتف هذا مسجل بالفعل.']);
        }

        // 4. 🛡️ نظام الحماية المتقدم (بصمة الجهاز + IP)
        $deviceId = $request->cookie('device_id');
        $ipAddress = $request->ip();

        $accountsCount = User::where(function($query) use ($deviceId, $ipAddress) {
            if ($deviceId) {
                $query->where('device_id', $deviceId);
            }
            $query->orWhere('ip_address', $ipAddress);
        })->count();

        if ($accountsCount >= 3) {
            throw ValidationException::withMessages([
                'contact' => 'عذراً، لقد وصلت للحد الأقصى لإنشاء الحسابات من هذا الجهاز (3 حسابات كحد أقصى).',
            ]);
        }

        $newCookie = false;
        if (!$deviceId) {
            $deviceId = (string) Str::uuid();
            $newCookie = true;
        }

        // 5. إنشاء كود الـ OTP
        $otp = (string) rand(1000, 9999);

        // 6. إنشاء المستخدم في الداتا بيز
        $user = User::create([
            'name' => $request->name,
            'email' => $isEmail ? $request->contact : null,
            'phone' => $isPhone ? $request->contact : null,
            'password' => Hash::make($request->password),
            'ip_address' => $ipAddress,
            'device_id' => $deviceId,
            'otp_code' => $otp,
            'otp_expires_at' => now()->addMinutes(10),
            'is_phone_verified' => false,
        ]);

        // 7. 🎁 منح اليوزر 100 نقطة هدية التسجيل فوراً
        $pointService = new PointService();
        $pointService->credit($user, 100, 'هدية ترحيبية بمناسبة الانضمام لمنصة نايلكس 🎁');

        // 8. 🟢 إرسال الـ OTP (باستخدام الكود القديم بتاعك للإيميل أو عبر شركة الـ SMS للموبايل)
        if ($isEmail) {
            Mail::raw("أهلاً بك في منصة Nilex. كود التفعيل الخاص بك هو: {$otp}", function ($message) use ($user) {
                $message->to($user->email)->subject('كود التفعيل - Nilex 🔐');
            });
        } else {
            // 🟢 إرسال الـ OTP للموبايل عبر خدمة الـ SMS
            $smsService = new SmsService();
            $smsService->sendOtp($user->phone, $otp);
        }

        event(new Registered($user));

        Auth::login($user);

        // 9. التوجيه لصفحة الـ OTP مع زرع الكوكيز
        $response = redirect()->route('otp.notice');
        
        if ($newCookie) {
            $response->cookie('device_id', $deviceId, 2628000);
        }

        return $response;
    }
}