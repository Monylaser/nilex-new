<?php

namespace App\Http\Controllers;

use App\Services\PaymobAdWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymobWebhookController extends Controller
{
    public function ads(Request $request, PaymobAdWebhookService $webhookService): JsonResponse
    {
        return $webhookService->handle($request);
    }
}
