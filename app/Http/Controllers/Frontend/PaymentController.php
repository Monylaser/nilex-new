<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\PointPlan;
use App\Models\Transaction;
use App\Services\PaymobService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymobService $paymobService,
    ) {}

    /**
     * بدء عملية الدفع لشراء نقاط
     */
    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'plan_id' => [
                'required',
                'integer',
                Rule::exists('point_plans', 'id')->where('is_active', true),
            ],
            'refund_policy_accepted' => ['accepted'],
        ], [
            'refund_policy_accepted.accepted' => __('server.payment.refund_required'),
        ]);

        $plan = PointPlan::query()
            ->whereKey($validated['plan_id'])
            ->where('is_active', true)
            ->firstOrFail();

        $user   = Auth::user();
        $points = (int) $plan->points;
        $amount = (float) $plan->price;

        if ($points <= 0 || $amount <= 0) {
            abort(422, 'Invalid plan configuration.');
        }

        $transaction = Transaction::create([
            'user_id'  => $user->id,
            'plan_id'  => $plan->id,
            'amount'   => $amount,
            'status'   => 'pending',
        ]);

        $token   = $this->paymobService->getAuthToken();
        $orderId = $this->paymobService->createOrder(
            $token,
            $amount,
            merchantOrderId: "nilex:{$transaction->id}",
        );

        $transaction->update([
            'paymob_order_id' => (string) $orderId,
        ]);

        $paymentKey = $this->paymobService->getPaymentKey($token, $orderId, $amount, $user);

        $iframeId = config('services.paymob.iframe_id');

        if (blank($iframeId)) {
            abort(503, 'Payment gateway is not configured.');
        }

        return redirect("https://accept.paymob.com/api/acceptance/iframes/{$iframeId}?payment_token={$paymentKey}");
    }

    /**
     * الـ Callback الذي يعيد Paymob إليه المستخدم بعد الدفع (عرض فقط — لا منطق نقاط هنا)
     */
    public function callback(Request $request)
    {
        return $request->query('success') === 'true'
            ? view('payment.success')
            : view('payment.failed');
    }
}
