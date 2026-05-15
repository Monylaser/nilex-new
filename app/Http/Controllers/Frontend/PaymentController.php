<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\PaymobService;
use App\Services\PointService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    protected $paymobService;

    public function __construct(PaymobService $paymobService)
    {
        $this->paymobService = $paymobService;
    }

    /**
     * بدء عملية الدفع لشراء نقاط
     */
    public function checkout(Request $request)
    {
        $request->validate([
            'package' => 'required|in:100,500,1000', // باقات النقاط المتاحة
        ]);

        $user = Auth::user();
        $amount = $request->package == 100 ? 50 : ($request->package == 500 ? 200 : 350); // أسعار افتراضية بالجنية

        // خطوات Paymob
        $token = $this->paymobService->getAuthToken();
        $orderId = $this->paymobService->createOrder($token, $amount);
        $paymentKey = $this->paymobService->getPaymentKey($token, $orderId, $amount, $user);

        // التوجيه لـ Iframe بتاع Paymob
        $iframeId = env('PAYMOB_IFRAME_ID');
        return redirect("https://egypt.paymob.com/api/acceptance/iframes/{$iframeId}?payment_token={$paymentKey}");
    }

    /**
     * الـ Callback اللي Paymob بترجع عليه بعد الدفع (نجاح أو فشل)
     */
    public function callback(Request $request)
    {
        $success = $request->query('success');
        
        if ($success === 'true') {
            // في حالة النجاح: بنعرض صفحة النجاح الشيك
            // ملاحظة: إضافة النقاط الفعلية بتتم عن طريق الـ Webhook لضمان الأمان
            return view('payment.success');
        }

        // في حالة الفشل: بنعرض صفحة الفشل
        return view('payment.failed');
    }
}