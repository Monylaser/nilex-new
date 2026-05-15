<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Services\SmsService; // 🟢 استدعاء خدمة الـ SMS

class OtpController extends Controller
{
    /**
     * عرض شاشة إدخال الكود
     */
    public function show()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // لو حسابه متفعل أصلاً، نوديه على لوحة التحكم
        if ($user->is_phone_verified) {
            return redirect()->route('dashboard');
        }

        return view('auth.verify-otp');
    }

    /**
     * التحقق من الكود اللي اليوزر دخله
     */
    public function verify(Request $request)
    {
        $request->validate([
            'otp' => 'required|numeric|digits:4',
        ], [
            'otp.required' => 'يرجى إدخال كود التفعيل',
            'otp.digits' => 'الكود يجب أن يتكون من 4 أرقام',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // هل الكود صح ولسه منتهاش؟ (صلاحيته 10 دقايق)
        if ($user->otp_code == $request->otp && now()->lessThanOrEqualTo($user->otp_expires_at)) {
            // تفعيل الحساب وتصفير الكود
            $user->update([
                'is_phone_verified' => true,
                'otp_code' => null,
                'otp_expires_at' => null
            ]);

            return redirect()->route('dashboard')->with('success', 'تم تفعيل حسابك بنجاح! 🎉');
        }

        return back()->withErrors(['otp' => 'الكود غير صحيح أو انتهت صلاحيته.']);
    }

    /**
     * إعادة إرسال كود جديد
     */
    public function resend()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // توليد كود جديد من 4 أرقام
        $otp = rand(1000, 9999);
        
        $user->update([
            'otp_code' => $otp,
            'otp_expires_at' => now()->addMinutes(10)
        ]);

        // 🟢 التعديل الذكي: توجيه الرسالة حسب نوع التسجيل (إيميل أو موبايل حقيقي)
        if ($user->email) {
            Mail::raw("أهلاً بك في منصة Nilex. كود التفعيل الخاص بك هو: {$otp}\n\nهذا الكود صالح لمدة 10 دقائق.", function ($message) use ($user) {
                $message->to($user->email)->subject('كود التفعيل - Nilex 🔐');
            });
        } else {
            // 🟢 استخدام خدمة الـ SMS لإعادة إرسال الكود للموبايل
            $smsService = new SmsService();
            $smsService->sendOtp($user->phone, $otp);
        }

        return back()->with('status', 'تم إرسال كود جديد بنجاح.');
    }
}