<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Category;
use App\Models\ListingPhoneClick;
use App\Models\ListingView;
use App\Models\ListingWhatsappClick;
use App\Models\Offer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class CategoryPerformanceWidget extends BaseWidget
{
    protected static ?int $sort = 9;

    protected int | string | array $columnSpan = 'full';

    protected string $view = 'filament.admin.widgets.category-performance-widget';

    public ?string $filter = 'last_7_days';

    public static function canView(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    /**
     * @return array<scalar, scalar>|null
     */
    public function getFilters(): ?array
    {
        return [
            'today'        => 'اليوم',
            'last_7_days'  => 'آخر 7 أيام',
            'last_30_days' => 'آخر 30 يوم',
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->getCategoryPerformanceQuery())
            ->heading('أداء الأقسام')
            ->description('مرتبة حسب إجمالي التواصل (هاتف + واتساب)، ثم CTR (هاتف ÷ مشاهدات)، ثم المشاهدات')
            ->columns([
                TextColumn::make('name_ar')
                    ->label('اسم القسم')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(fn (Category $record): ?string => $record->name_ar),

                TextColumn::make('views_count')
                    ->label('المشاهدات')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('phone_clicks_count')
                    ->label('نقرات الهاتف')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('whatsapp_clicks_count')
                    ->label('نقرات الواتساب')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('total_leads')
                    ->label('إجمالي التواصل')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('offers_count')
                    ->label('العروض')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('ctr')
                    ->label('CTR')
                    ->formatStateUsing(fn ($state): string => $this->formatPercent((float) ($state ?? 0)))
                    ->sortable(),
            ])
            ->paginated(false)
            ->defaultSort('total_leads', 'desc');
    }

    protected function getCategoryPerformanceQuery(): Builder
    {
        $startDate  = $this->resolveFilterStartDate();
        $aggregated = $this->buildAggregatedCategoriesSubquery($startDate);

        return Category::query()
            ->fromSub($aggregated, 'categories')
            ->select([
                'categories.id',
                'categories.name_ar',
                'categories.views_count',
                'categories.phone_clicks_count',
                'categories.whatsapp_clicks_count',
                'categories.offers_count',
                'categories.total_leads',
            ])
            ->selectRaw(
                'CASE WHEN categories.views_count > 0
                    THEN (categories.phone_clicks_count * 100.0 / categories.views_count)
                    ELSE 0
                END AS ctr'
            )
            ->orderByDesc('categories.total_leads')
            ->orderByDesc('ctr')
            ->orderByDesc('categories.views_count')
            ->limit(10);
    }

    protected function buildAggregatedCategoriesSubquery(Carbon $startDate): Builder
    {
        // ملاحظة: هذه الاستعلامات تبدأ من جداول الأحداث (وليس من Listing)،
        // لذا لا يُطبَّق global scope الخاص بـ SoftDeletes تلقائياً — نستبعد
        // الإعلانات المحذوفة ناعماً يدوياً بـ whereNull('listings.deleted_at').
        $viewsByCat = ListingView::query()
            ->join('listings', 'listings.id', '=', 'listing_views.listing_id')
            ->whereNull('listings.deleted_at')
            ->where('listing_views.created_at', '>=', $startDate)
            ->selectRaw('listings.category_id AS category_id, COUNT(*) AS views_count')
            ->groupBy('listings.category_id');

        $phoneByCat = ListingPhoneClick::query()
            ->join('listings', 'listings.id', '=', 'listing_phone_clicks.listing_id')
            ->whereNull('listings.deleted_at')
            ->where('listing_phone_clicks.created_at', '>=', $startDate)
            ->selectRaw('listings.category_id AS category_id, COUNT(*) AS phone_clicks_count')
            ->groupBy('listings.category_id');

        $whatsappByCat = ListingWhatsappClick::query()
            ->join('listings', 'listings.id', '=', 'listing_whatsapp_clicks.listing_id')
            ->whereNull('listings.deleted_at')
            ->where('listing_whatsapp_clicks.created_at', '>=', $startDate)
            ->selectRaw('listings.category_id AS category_id, COUNT(*) AS whatsapp_clicks_count')
            ->groupBy('listings.category_id');

        $offersByCat = Offer::query()
            ->join('listings', 'listings.id', '=', 'offers.listing_id')
            ->whereNull('listings.deleted_at')
            ->where('offers.created_at', '>=', $startDate)
            ->selectRaw('listings.category_id AS category_id, COUNT(*) AS offers_count')
            ->groupBy('listings.category_id');

        return Category::query()
            ->select([
                'categories.id',
                'categories.name_ar',
            ])
            ->selectRaw('COALESCE(v.views_count, 0) AS views_count')
            ->selectRaw('COALESCE(p.phone_clicks_count, 0) AS phone_clicks_count')
            ->selectRaw('COALESCE(w.whatsapp_clicks_count, 0) AS whatsapp_clicks_count')
            ->selectRaw('COALESCE(o.offers_count, 0) AS offers_count')
            ->selectRaw('(COALESCE(p.phone_clicks_count, 0) + COALESCE(w.whatsapp_clicks_count, 0)) AS total_leads')
            ->leftJoinSub($viewsByCat, 'v', 'v.category_id', '=', 'categories.id')
            ->leftJoinSub($phoneByCat, 'p', 'p.category_id', '=', 'categories.id')
            ->leftJoinSub($whatsappByCat, 'w', 'w.category_id', '=', 'categories.id')
            ->leftJoinSub($offersByCat, 'o', 'o.category_id', '=', 'categories.id')
            ->where('categories.is_active', true)
            ->where(function (Builder $query): void {
                $query->whereRaw('COALESCE(v.views_count, 0) > 0')
                    ->orWhereRaw('COALESCE(p.phone_clicks_count, 0) > 0')
                    ->orWhereRaw('COALESCE(w.whatsapp_clicks_count, 0) > 0')
                    ->orWhereRaw('COALESCE(o.offers_count, 0) > 0');
            });
    }

    private function resolveFilterStartDate(): Carbon
    {
        return match ($this->filter) {
            'today'        => Carbon::today(),
            'last_30_days' => Carbon::now()->subDays(29)->startOfDay(),
            default        => Carbon::now()->subDays(6)->startOfDay(),
        };
    }

    private function formatPercent(float $value): string
    {
        return number_format($value, 2) . '%';
    }
}
