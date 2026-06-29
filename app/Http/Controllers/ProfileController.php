<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        // ملاحظة: رقم الهاتف (phone) لم يعد يُحفَظ من هذا النموذج مباشرةً —
        // صار يُدار حصراً عبر مسار توثيق الهاتف المنفصل (PhoneVerificationController)
        // الذي يرسل OTP للرقم الجديد ويؤكده قبل اعتماده. لذا أُزيل من قواعد التحقق هنا.
        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'email'      => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'whatsapp'   => ['nullable', 'string', 'max:20'],
            'avatar'     => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'governorate'=> ['nullable', 'string', 'max:100'],
            'city'       => ['nullable', 'string', 'max:100'],
            'bio'        => ['nullable', 'string', 'max:500'],
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        // رفع الصورة
        if ($request->hasFile('avatar')) {
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }
            $validated['avatar'] = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = $validated['avatar'];
        }

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * حذف الحساب = "تجميد" (Anonymize) بدل الحذف الفيزيائي.
     *
     * نسمح بالحذف لكل المستخدمين (حتى من لهم تاريخ بيع/تقييم)، لكن بدل
     * $user->delete() — الذي كان hard delete يكسر قيود restrictOnDelete على
     * sale_confirmations/reviews ويُسقط الإعلانات عبر cascade — نُبقي الصف
     * بنفس الـ ID (لحماية الـ FK) ونستبدل كل البيانات الحساسة بقيم مجهّلة،
     * ونعمل soft-delete لإعلانات المستخدم حتى تختفي من العامة وتبقى للسجل.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        // احذف ملف الأفاتار من القرص فعلياً (لا مجرد تصفير العمود).
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        DB::transaction(function () use ($user) {
            // إخفاء كل إعلانات المستخدم من العامة مع إبقائها للسجل (SoftDeletes).
            $user->listings()->get()->each->delete();

            $user->forceFill([
                'name'             => __('server.account.deleted_name'),
                // إيميل وهمي فريد لكل حساب (قيد unique على email) — يتضمّن الـ id والوقت.
                'email'            => 'deleted-' . $user->id . '-' . now()->timestamp . '@nilex.local',
                'email_verified_at'=> null,
                'phone'            => null,
                'is_phone_verified'=> false,
                'password'         => Hash::make(Str::random(40)),
                'avatar'           => null,
                // تصفير السوشيال لمنع إعادة الدخول عبر Socialite (يطابق بالـ provider_id بلا باسورد).
                'provider_name'    => null,
                'provider_id'      => null,
                // تصفير بصمات الجهاز/التتبّع.
                'device_id'        => null,
                'ip_address'       => null,
                'fingerprint_hash' => null,
                'otp_code'         => null,
                'otp_expires_at'   => null,
                'remember_token'   => null,
                'anonymized_at'    => now(),
            ])->save();
        });

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}