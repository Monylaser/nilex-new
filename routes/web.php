<?php

use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\CategoryController;
use App\Http\Controllers\Frontend\PaymentController;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\ProfileController;
use App\Livewire\Frontend\UserDashboard;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Web Routes - NILEX Platform
|--------------------------------------------------------------------------
*/

// ✅ الصفحة الرئيسية ومحرك البحث
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/search', [HomeController::class, 'search'])->name('listings.search');

// ✅ مسارات العرض العامة (إعلانات وأقسام)
Route::get('/listings/{listing}', [ListingController::class, 'show'])->name('listings.show');
Route::get('/category/{category:slug}', [CategoryController::class, 'show'])->name('category.show');

// 🟢 مسار الكشف عن الرقم وتتبع النقرات (محمي برمجياً)
Route::post('/listings/{listing}/reveal-phone', function (App\Models\Listing $listing) {
    // 1. التحقق من تسجيل الدخول
    if (!Auth::check()) {
        return response()->json(['error' => 'Unauthenticated'], 401);
    }

    // 2. تسجيل النقرة في الإحصائيات للبائع
    $listing->increment('whatsapp_clicks');

    // 3. تجهيز الرقم
    $phone = $listing->phone ?? $listing->user->phone;
    $phoneForWhatsapp = '2' . ltrim($phone, '0');
    $message = urlencode("مرحباً، بخصوص إعلانك: {$listing->title} على منصة Nilex. هل ما زال متاحاً؟");

    // 4. إرجاع البيانات بشكل آمن
    return response()->json([
        'phone' => $phone,
        'whatsapp_url' => "https://wa.me/{$phoneForWhatsapp}?text={$message}"
    ]);
})->name('listings.reveal-phone');

// 🛡️ المسارات المحمية (تحتاج تسجيل دخول وتوثيق OTP)
Route::middleware(['auth', \App\Http\Middleware\EnsureOtpIsVerified::class])->group(function () {
    
    // 1. إدارة الإعلانات (إضافة وحفظ)
    Route::get('/listings/create', [HomeController::class, 'create'])->name('listings.create');
    Route::post('/listings/store', [HomeController::class, 'store'])->name('listings.store');

    // 2. لوحة التحكم (Dashboard)
    Route::get('/dashboard', UserDashboard::class)->middleware(['verified'])->name('dashboard');

    // 3. إدارة الملف الشخصي (Profile)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // 4. سجل العمليات والنقاط (Points History)
    Route::get('/points/history', function () {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $transactions = $user->pointTransactions()->latest()->paginate(10);
        return view('points.history', compact('transactions'));
    })->name('points.history');

    // 5. المدفوعات وشحن النقاط (Paymob)
    Route::post('/payment/checkout', [PaymentController::class, 'checkout'])->name('payment.checkout');
    Route::get('/payment/callback', [PaymentController::class, 'callback'])->name('payment.callback');

    // 6. 🤝 إرسال عرض سعر (Make an Offer) 
    Route::post('/listings/{listing}/offer', [ListingController::class, 'makeOffer'])->name('listings.offer');
});

// ✅ مسار الـ Webhook للمدفوعات (خارج الـ Middleware)
Route::post('/payment/webhook', [PaymentController::class, 'webhook'])->name('payment.webhook');

// تحميل مسارات المصادقة (Breeze)
require __DIR__.'/auth.php';