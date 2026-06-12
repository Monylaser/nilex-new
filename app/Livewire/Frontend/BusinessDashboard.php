<?php

namespace App\Livewire\Frontend;

use App\Models\Listing;
use App\Models\PlanEntitlement;
use App\Services\EntitlementService;
use App\Services\MonthlyReportService;
use App\Services\SellerListingAnalyticsService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.app')]
class BusinessDashboard extends Component
{
    public function mount(EntitlementService $entitlements): void
    {
        $user = Auth::user();

        if (! $entitlements->hasFeature($user, EntitlementService::FEATURE_BUSINESS_DASHBOARD)) {
            session()->flash('error', __('ui.analytics.business_access_denied'));
            $this->redirectRoute('dashboard');
        }
    }

    public function exportCsv(SellerListingAnalyticsService $analytics): StreamedResponse
    {
        $user = Auth::user();
        $listings = Listing::query()
            ->where('user_id', $user->id)
            ->with('category')
            ->get();

        $filename = 'business-analytics-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($listings): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Title', 'Category', 'Price', 'Views', 'WhatsApp Clicks', 'Status']);

            foreach ($listings as $listing) {
                fputcsv($handle, [
                    $listing->id,
                    $listing->title,
                    $listing->category?->name_ar ?? '',
                    $listing->price,
                    $listing->views_count,
                    $listing->whatsapp_clicks,
                    $listing->status,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function render(
        SellerListingAnalyticsService $analytics,
        MonthlyReportService $reportService,
        EntitlementService $entitlements,
    ) {
        $user = Auth::user();

        $listings = Listing::query()
            ->where('user_id', $user->id)
            ->with('category')
            ->latest()
            ->get();

        return view('livewire.frontend.business-dashboard', [
            'user'                  => $user,
            'listings'              => $listings,
            'topListings'           => $analytics->getTopPerformingListings($user),
            'monthlyPerformance'    => $analytics->getMonthlyPerformance($user),
            'competitorComparison'  => $analytics->getCompetitorPriceComparison($user),
            'pointsSpentOnBoosts'    => $reportService->getBoostPointsSpent($user),
            'currentTier'           => $user->plan_tier ?? PlanEntitlement::TIER_STARTER,
            'hasBusinessDashboard'  => $entitlements->hasFeature($user, EntitlementService::FEATURE_BUSINESS_DASHBOARD),
        ]);
    }
}
