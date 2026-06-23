<?php

use App\Http\Controllers\AdSpacesController;
use App\Http\Controllers\AdTrackingController;
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\CategoryController;
use App\Http\Controllers\Frontend\LegalPageController;
use App\Http\Controllers\Frontend\PaymentController;
use App\Http\Controllers\PaymobController;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\OtpController;          // ✅ بدل OtpVerificationController
use App\Http\Controllers\Auth\SocialiteController;    // ✅ بدل NilexAuthController
use App\Livewire\Frontend\UserDashboard;
use App\Livewire\Frontend\BusinessDashboard;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Web Routes - NILEX Platform
|--------------------------------------------------------------------------
*/

// 🌐 Language Switcher
Route::post('/language/{locale}', function (string $locale) {
    $available = config('app.available_locales', ['ar', 'en']);
    if (in_array($locale, $available)) {
        session(['locale' => $locale]);

        // Persist to the user record so the choice follows them across devices.
        if (Auth::check()) {
            Auth::user()->forceFill(['locale' => $locale])->save();
        }
    }
    return redirect()->back();
})->name('language.switch');

// ✅ الصفحة الرئيسية ومحرك البحث
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/search', [HomeController::class, 'search'])->name('listings.search');
Route::get('/pricing', [HomeController::class, 'pricing'])->name('pricing');

// ✅ مسارات العرض العامة للأقسام
Route::get('/category/{category:slug}', [CategoryController::class, 'show'])->name('category.show');

// 🌅 مسارات السوشيال ميديا — SocialiteController ✅
Route::get('/auth/{provider}/redirect', [SocialiteController::class, 'redirect'])
    ->name('auth.social.redirect')
    ->where('provider', 'google|facebook|tiktok|instagram');

Route::get('/auth/{provider}/callback', [SocialiteController::class, 'callback'])
    ->name('auth.social.callback')
    ->where('provider', 'google|facebook|tiktok|instagram');

// 🔑 مسارات OTP — OtpController ✅
Route::middleware(['auth'])->group(function () {
    Route::get('/verify-otp', [OtpController::class, 'show'])->name('otp.notice');
    Route::post('/verify-otp', [OtpController::class, 'verify'])
        ->middleware('throttle:otp-verify')
        ->name('otp.verify');
    Route::post('/verify-otp/resend', [OtpController::class, 'resend'])
        ->middleware('throttle:otp-resend')
        ->name('otp.resend');
});

// 🛡️ المسارات المحمية (تسجيل دخول + OTP)
Route::middleware(['auth', 'otp.verified'])->group(function () {

    // 1. إدارة الإعلانات (يجب أن تكون قبل مسار عرض الإعلان العام)
    Route::get('/listings/create', [HomeController::class, 'create'])->name('listings.create');
    Route::post('/listings/store', [HomeController::class, 'store'])->name('listings.store');
    // 🤖 المساعد الذكي لتوليد بيانات الإعلان (Gemini) — يُستخدم داخل ويزارد الإضافة
    Route::post('/listings/ai-generate', [HomeController::class, 'aiGenerate'])->name('listings.ai-generate');

    // 2. لوحة التحكم
    Route::get('/dashboard', UserDashboard::class)
    ->name('dashboard'); // تم إزالة الـ middleware المكرر هنا لأن الجروب بيقوم بالدور

    Route::get('/dashboard/leads', \App\Livewire\Frontend\SellerLeads::class)
        ->name('dashboard.leads');

    Route::get('/dashboard/leads/{lead}', \App\Livewire\Frontend\SellerLeadDetail::class)
        ->name('dashboard.leads.show');

    Route::get('/business/dashboard', BusinessDashboard::class)
        ->name('business.dashboard');

    // 3. الملف الشخصي
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // 4. سجل النقاط
    Route::get('/points/history', function () {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $transactions = $user->pointTransactions()->latest()->paginate(10);
        return view('points.history', compact('transactions'));
    })->name('points.history');

    // 5. المدفوعات
    Route::post('/payment/checkout', [PaymentController::class, 'checkout'])->name('payment.checkout');
    Route::get('/payment/callback', [PaymentController::class, 'callback'])->name('payment.callback');

    // 6. الرسائل الفورية
    Route::post('/messages', [MessageController::class, 'store'])->name('messages.store');

    // 7. عروض الشراء
    Route::post('/listings/{listing}/offer', [ListingController::class, 'makeOffer'])->name('listings.offer');

}); // ✅ إغلاق الـ middleware group

// ✅ مسار عرض تفاصيل الإعلان (تم نقله هنا لأسفل لتفادي تعارض الـ 404 مع listings/create)
Route::get('/listings/{listing}', [ListingController::class, 'show'])->name('listings.show');

// 🟢 كشف الرقم وتتبع النقرات (يجب أن يكونوا هنا تحت الـ middleware جروب أو داخله حسب متطلباتك)
Route::post('/listings/{listing}/reveal-phone', [ListingController::class, 'revealPhone'])
    ->name('listings.reveal-phone');
Route::post('/listings/{listing}/whatsapp-click', [ListingController::class, 'trackWhatsappClick'])
    ->name('listings.whatsapp-click');

// 🤝 Paymob server callbacks (no auth / no CSRF)
Route::post('/payments/callback', [PaymobController::class, 'callback'])->name('payments.callback');
Route::post('/payment/webhook', [PaymobController::class, 'callback'])->name('payment.webhook');

// تحميل مسارات المصادقة (Breeze)
require __DIR__.'/auth.php';

// 📢 مسارات الإعلانات والتتبع
Route::get('/ads/pricing', [AdSpacesController::class, 'index'])->name('ads.pricing');
Route::get('/ads/{campaign}/impression', [AdTrackingController::class, 'impression'])->name('ads.impression');
Route::get('/ads/{campaign}/click', [AdTrackingController::class, 'click'])->name('ads.click');

// ✅ الصفحات القانونية — catch-all يجب أن يكون آخر مسار حتى لا يلتهم المسارات المحددة
Route::get('/{slug}', [LegalPageController::class, 'show'])->name('legal.show');