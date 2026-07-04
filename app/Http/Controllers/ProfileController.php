<?php

namespace App\Http\Controllers;

use App\Models\Location;
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
        $user = $request->user();
        $user->load('location');

        $governorates = Location::governorates()
            ->active()
            ->with(['children' => fn ($q) => $q->active()->orderBy('sort_order')])
            ->get(['id', 'name_ar', 'name_en', 'slug']);

        // Derive the governorate ID from the user's city location parent.
        $userGovernorateId = $user->location?->parent_id ? (string) $user->location->parent_id : '';
        $userLocationId    = $user->location_id          ? (string) $user->location_id          : '';

        return view('profile.edit', compact('user', 'governorates', 'userGovernorateId', 'userLocationId'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        // رقم الهاتف: يُدار حصراً عبر PhoneVerificationController (OTP).
        // الإيميل: يُدار عبر EmailVerificationProfileController إذا أُضيف لأول مرة،
        //           وعبر هذا النموذج فقط إذا كان المستخدم لديه إيميل مسبقاً.
        $emailRules = $user->email
            ? ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email,' . $user->id]
            : ['prohibited']; // المستخدمون بالموبايل يضيفون إيميلهم عبر قسم توثيق الإيميل

        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'email'       => $emailRules,
            'whatsapp'    => ['nullable', 'string', 'max:20'],
            'avatar'      => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'bio'         => ['nullable', 'string', 'max:500'],
        ]);

        // Preserve the original email for dirty-checking before any writes.
        $originalEmail = $user->getOriginal('email') ?? $user->email;

        $user->fill([
            'name'        => $validated['name'],
            'whatsapp'    => $validated['whatsapp'] ?? null,
            'location_id' => $validated['location_id'] ?? null,
            'bio'         => $validated['bio'] ?? null,
        ]);

        // Email — only for users who already have one (phone-only users are 'prohibited').
        if (isset($validated['email'])) {
            $user->email = $validated['email'];
            if ($validated['email'] !== $originalEmail) {
                $user->email_verified_at = null;
            }
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