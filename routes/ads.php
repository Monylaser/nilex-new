<?php

use App\Http\Controllers\PaymobWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Self-Service Advertising Routes
|--------------------------------------------------------------------------
|
| Isolated from points checkout and existing Paymob webhook routes.
|
*/

Route::post('/webhooks/paymob/ads', [PaymobWebhookController::class, 'ads'])
    ->middleware('self_service_ads')
    ->name('webhooks.paymob.ads');
