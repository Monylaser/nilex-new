<?php

use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Frontend\CategoryController;
/*
|--------------------------------------------------------------------------
| Web Routes - NILEX Platform
|--------------------------------------------------------------------------
*/

// ✅ الصفحة الرئيسية
Route::get('/', [HomeController::class, 'index'])->name('home');

// ✅ محرك البحث
Route::get('/search', [HomeController::class, 'search'])->name('listings.search');


Route::get('/category/{category:slug}', [CategoryController::class, 'show'])->name('category.show');


// ✅ مسارات الإعلانات (عرض، إضافة، حفظ)
Route::get('/listing/{listing}', [HomeController::class, 'show'])->name('listings.show');
Route::get('/category/{category:slug}', [CategoryController::class, 'show'])->name('category.show');

// ✅ مسارات تحتاج تسجيل دخول (Listings Management)
Route::middleware('auth')->group(function () {
    Route::get('/listings/create', [HomeController::class, 'create'])->name('listings.create');
    Route::post('/listings/store', [HomeController::class, 'store'])->name('listings.store');
});

// ✅ لوحة التحكم (Dashboard)
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// ✅ مسارات المستخدمين المسجلين (Profile & Points)
Route::middleware('auth')->group(function () {

    // 1. إدارة الملف الشخصي (Profile)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // 2. سجل العمليات والنقاط (Points History)
    Route::get('/points/history', function () {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $transactions = $user->pointTransactions()->latest()->paginate(10);
        return view('points.history', compact('transactions'));
    })->name('points.history');

});

// تحميل مسارات المصادقة (Breeze)
require __DIR__.'/auth.php';
