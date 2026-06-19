<?php

use App\Http\Controllers\Dashboard\SellerAdCampaignController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Seller Self-Service Advertising Routes
|--------------------------------------------------------------------------
|
| Protected by auth, verified email, and self_service_ads feature flag.
| Isolated from admin Filament and public ad tracking routes.
|
*/

Route::middleware(['auth', 'otp.verified', 'self_service_ads'])
    ->prefix('dashboard/ads')
    ->name('dashboard.ads.')
    ->group(function () {
        Route::get('/', [SellerAdCampaignController::class, 'index'])->name('index');
        Route::get('/create', [SellerAdCampaignController::class, 'create'])->name('create');
        Route::post('/create', [SellerAdCampaignController::class, 'store'])->name('store');
        Route::get('/{campaign}', [SellerAdCampaignController::class, 'show'])->name('show');
        Route::post('/{campaign}/retry-payment', [SellerAdCampaignController::class, 'retryPayment'])->name('retry-payment');
    });
