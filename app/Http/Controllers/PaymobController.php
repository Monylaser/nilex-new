<?php

namespace App\Http\Controllers;

use App\Services\PaymobWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymobController extends Controller
{
    public function callback(Request $request, PaymobWebhookService $webhookService): JsonResponse
    {
        return $webhookService->handle($request);
    }
}
