<?php

namespace App\Services;

use App\Models\Listing;
use App\Models\ListingPhoneClick;
use App\Models\ListingView;
use App\Models\ListingWhatsappClick;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SellerListingAnalyticsService
{
    /**
     * Minimum number of (non-canceled) offers a seller must have received
     * before a response rate is statistically meaningful. Below this, the
     * methods return null ("insufficient data") instead of a misleading number.
     */
    public const RESPONSE_RATE_MIN_OFFERS = 5;

    /**
     * Percentage of received offers the seller has actually decided on
     * (accepted or rejected), out of all non-canceled offers received.
     *
     * Canceled offers are excluded from BOTH numerator and denominator —
     * a cancel is a buyer action, not a seller response opportunity.
     *
     * Returns null when fewer than RESPONSE_RATE_MIN_OFFERS offers exist.
     */
    public function responseRate(User $seller): ?float
    {
        $total = (int) Offer::query()
            ->where('receiver_id', $seller->id)
            ->where('status', '!=', 'canceled')
            ->count();

        if ($total < self::RESPONSE_RATE_MIN_OFFERS) {
            return null;
        }

        $responded = (int) Offer::query()
            ->where('receiver_id', $seller->id)
            ->whereNotIn('status', ['pending', 'canceled'])
            ->count();

        return round(($responded / $total) * 100, 1);
    }

    /**
     * Average seller response time, in seconds, computed from
     * (responded_at - created_at) over offers that have a responded_at.
     *
     * Honors the same minimum-offers threshold as responseRate(); returns
     * null when there is not enough data (or no responded offers).
     */
    public function averageResponseTime(User $seller): ?float
    {
        $total = (int) Offer::query()
            ->where('receiver_id', $seller->id)
            ->where('status', '!=', 'canceled')
            ->count();

        if ($total < self::RESPONSE_RATE_MIN_OFFERS) {
            return null;
        }

        $offers = Offer::query()
            ->where('receiver_id', $seller->id)
            ->whereNotNull('responded_at')
            ->get(['created_at', 'responded_at']);

        if ($offers->isEmpty()) {
            return null;
        }

        $totalSeconds = $offers->sum(
            fn (Offer $offer): int => $offer->responded_at->getTimestamp() - $offer->created_at->getTimestamp()
        );

        return round($totalSeconds / $offers->count(), 1);
    }

    public function totalViewsForUser(User $user): int
    {
        return (int) ListingView::query()
            ->whereHas('listing', fn ($query) => $query->where('user_id', $user->id))
            ->count();
    }

    public function totalPhoneClicksForUser(User $user): int
    {
        return (int) ListingPhoneClick::query()
            ->whereHas('listing', fn ($query) => $query->where('user_id', $user->id))
            ->count();
    }

    public function totalWhatsappClicksForUser(User $user): int
    {
        return (int) ListingWhatsappClick::query()
            ->whereHas('listing', fn ($query) => $query->where('user_id', $user->id))
            ->count();
    }

    public function getDashboardStats(User $user): array
    {
        return [
            'views_events'           => $this->totalViewsForUser($user),
            'phone_clicks'           => $this->totalPhoneClicksForUser($user),
            'whatsapp_clicks_events' => $this->totalWhatsappClicksForUser($user),
        ];
    }

    public function getConversionRate(User $user): float
    {
        $views = (int) Listing::query()
            ->where('user_id', $user->id)
            ->sum('views_count');

        if ($views === 0) {
            return 0.0;
        }

        $whatsappClicks = (int) Listing::query()
            ->where('user_id', $user->id)
            ->sum('whatsapp_clicks');

        return round(($whatsappClicks / $views) * 100, 2);
    }

    public function getViewsByDay(User $user, int $days = 30): array
    {
        $start = now()->subDays($days - 1)->startOfDay();

        $counts = ListingView::query()
            ->whereHas('listing', fn ($query) => $query->where('user_id', $user->id))
            ->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $labels = [];
        $values = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $start->copy()->addDays($i);
            $key  = $date->format('Y-m-d');
            $labels[] = $date->format('m/d');
            $values[] = (int) ($counts[$key] ?? 0);
        }

        return ['labels' => $labels, 'values' => $values];
    }

    public function getWhatsappClicksByListing(User $user, int $limit = 10): array
    {
        $listings = Listing::query()
            ->where('user_id', $user->id)
            ->orderByDesc('whatsapp_clicks')
            ->limit($limit)
            ->get(['id', 'title', 'whatsapp_clicks']);

        return [
            'labels' => $listings->map(fn (Listing $l) => mb_substr($l->title, 0, 20))->values()->all(),
            'values' => $listings->pluck('whatsapp_clicks')->map(fn ($v) => (int) $v)->values()->all(),
        ];
    }

    public function getCategoryPerformance(User $user): array
    {
        $nameColumn = app()->getLocale() === 'ar' ? 'categories.name_ar' : 'categories.name_en';

        $rows = Listing::query()
            ->where('listings.user_id', $user->id)
            ->join('categories', 'categories.id', '=', 'listings.category_id')
            ->select(
                "{$nameColumn} as category_name",
                DB::raw('SUM(listings.views_count) as total_views'),
            )
            ->groupBy('categories.id', $nameColumn)
            ->orderByDesc('total_views')
            ->limit(8)
            ->get();

        return [
            'labels' => $rows->pluck('category_name')->values()->all(),
            'values' => $rows->pluck('total_views')->map(fn ($v) => (int) $v)->values()->all(),
        ];
    }

    public function getMonthlyPerformance(User $user, int $months = 6): array
    {
        $start = now()->subMonths($months - 1)->startOfMonth();

        $grouped = ListingView::query()
            ->whereHas('listing', fn ($query) => $query->where('user_id', $user->id))
            ->where('created_at', '>=', $start)
            ->get(['created_at'])
            ->groupBy(fn (ListingView $view) => $view->created_at->format('Y-m'))
            ->map(fn ($group) => $group->count());

        $labels = [];
        $values = [];

        for ($i = 0; $i < $months; $i++) {
            $date = $start->copy()->addMonths($i);
            $key  = $date->format('Y-m');
            $labels[] = $date->translatedFormat('M Y');
            $values[] = (int) ($grouped[$key] ?? 0);
        }

        return ['labels' => $labels, 'values' => $values];
    }

    public function getTopPerformingListings(User $user, int $limit = 5): Collection
    {
        return Listing::query()
            ->where('user_id', $user->id)
            ->with('category')
            ->orderByRaw('(views_count + whatsapp_clicks) DESC')
            ->limit($limit)
            ->get();
    }

    public function getCompetitorPriceComparison(User $user): array
    {
        $listings = Listing::query()
            ->where('user_id', $user->id)
            ->where('status', Listing::STATUS_PUBLISHED)
            ->with('category')
            ->get();

        return $listings->map(function (Listing $listing) {
            $avgPrice = Listing::query()
                ->where('category_id', $listing->category_id)
                ->where('status', Listing::STATUS_PUBLISHED)
                ->where('user_id', '!=', $listing->user_id)
                ->avg('price');

            return [
                'listing_id'    => $listing->id,
                'title'         => $listing->title,
                'price'         => (float) $listing->price,
                'category'      => $listing->category?->name ?? '—',
                'avg_category'  => $avgPrice ? round((float) $avgPrice, 2) : null,
                'diff_percent'  => $avgPrice && $avgPrice > 0
                    ? round((($listing->price - $avgPrice) / $avgPrice) * 100, 1)
                    : null,
            ];
        })->all();
    }

    public function getLastMonthStats(User $user): array
    {
        $start = now()->subMonth()->startOfMonth();
        $end   = now()->subMonth()->endOfMonth();

        return [
            'views'            => ListingView::query()
                ->whereHas('listing', fn ($q) => $q->where('user_id', $user->id))
                ->whereBetween('created_at', [$start, $end])
                ->count(),
            'phone_clicks'     => ListingPhoneClick::query()
                ->whereHas('listing', fn ($q) => $q->where('user_id', $user->id))
                ->whereBetween('created_at', [$start, $end])
                ->count(),
            'whatsapp_clicks'  => ListingWhatsappClick::query()
                ->whereHas('listing', fn ($q) => $q->where('user_id', $user->id))
                ->whereBetween('created_at', [$start, $end])
                ->count(),
            'period_label'     => $start->translatedFormat('F Y'),
        ];
    }
}
