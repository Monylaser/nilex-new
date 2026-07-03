<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\StoreSellerAdCampaignRequest;
use App\Models\AdCampaign;
use App\Models\Category;
use App\Services\AdCampaignPaymentService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;

class SellerAdCampaignController extends Controller
{
    use AuthorizesRequests;
    public function __construct(
        protected AdCampaignPaymentService $paymentService,
    ) {}

    public function index(): View
    {
        $campaigns = AdCampaign::query()
            ->where('seller_id', Auth::id())
            ->latest()
            ->paginate(15);

        return view('dashboard.ads.index', [
            'campaigns' => $campaigns,
        ]);
    }

    public function create(): View
    {
        $categories = Category::query()
            ->active()
            ->orderBy('sort_order')
            ->get(['id', 'name_ar', 'name_en']);

        return view('dashboard.ads.create', [
            'categories'     => $categories,
            'placements'   => $this->sellerPlacements(),
            'durations'      => config('ad_pricing.durations', []),
            'pricingConfig'  => $this->pricingConfigForFrontend(),
            'currency'       => config('ad_pricing.currency', 'EGP'),
        ]);
    }

    public function store(StoreSellerAdCampaignRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $durationDays = (int) $validated['duration_days'];

        try {
            $checkoutUrl = DB::transaction(function () use ($validated, $durationDays) {
                $campaign = AdCampaign::create([
                    'title'            => $validated['title'],
                    'placement'        => $validated['placement'],
                    'category_id'      => $validated['placement'] === 'category_page'
                        ? $validated['category_id']
                        : null,
                    'target_url'       => $validated['target_url'] ?? null,
                    'duration_days'    => $durationDays,
                    'status'           => 'draft',
                    'approval_status'  => 'pending',
                    'payment_status'   => 'pending',
                    'seller_id'        => Auth::id(),
                    'created_by'       => Auth::id(),
                ]);

                $campaign
                    ->addMediaFromRequest('ad_image')
                    ->toMediaCollection('ad_image');

                return $this->paymentService->initiatePayment(
                    $campaign,
                    Auth::user(),
                    $durationDays,
                );
            });
        } catch (RuntimeException $exception) {
            return back()
                ->withInput()
                ->withErrors(['payment' => $exception->getMessage()]);
        }

        return redirect()->away($checkoutUrl);
    }

    public function show(AdCampaign $campaign): View
    {
        $this->authorize('view', $campaign);

        $campaign->load(['category', 'media', 'paymentAttempts' => fn ($query) => $query->latest()]);

        $expectedAmount = null;

        if ($campaign->duration_days !== null) {
            try {
                $expectedAmount = $this->paymentService->calculatePrice(
                    $campaign->placement,
                    (int) $campaign->duration_days,
                );
            } catch (RuntimeException) {
                $expectedAmount = null;
            }
        }

        return view('dashboard.ads.show', [
            'campaign'       => $campaign,
            'expectedAmount' => $expectedAmount,
            'currency'       => config('ad_pricing.currency', 'EGP'),
        ]);
    }

    public function retryPayment(AdCampaign $campaign): RedirectResponse
    {
        $this->authorize('retryPayment', $campaign);

        if ($campaign->duration_days === null) {
            return back()->withErrors(['payment' => __('server.ads.duration_missing')]);
        }

        try {
            $checkoutUrl = $this->paymentService->initiatePayment(
                $campaign,
                Auth::user(),
                (int) $campaign->duration_days,
            );
        } catch (RuntimeException $exception) {
            return back()->withErrors(['payment' => $exception->getMessage()]);
        }

        return redirect()->away($checkoutUrl);
    }

    private function sellerPlacements(): array
    {
        $keys = ['hero_top', 'home_feed', 'category_page', 'login_page', 'popup'];
        $placements = config('ad_pricing.placements', []);

        return collect($keys)
            ->mapWithKeys(fn (string $key) => [
                $key => $placements[$key] ?? ['label_ar' => $key],
            ])
            ->all();
    }

    private function pricingConfigForFrontend(): array
    {
        $keys = ['hero_top', 'home_feed', 'category_page', 'login_page', 'popup'];
        $placements = config('ad_pricing.placements', []);

        return collect($keys)
            ->mapWithKeys(fn (string $key) => [
                $key => [
                    'label_ar' => $placements[$key]['label_ar'] ?? $key,
                    'prices'   => $placements[$key]['prices'] ?? [],
                ],
            ])
            ->all();
    }
}
