<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Listing;
use App\Models\ListingPhoneClick;
use App\Models\ListingView;
use App\Models\ListingWhatsappClick;
use App\Models\Offer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class TopListingsWidget extends BaseWidget
{
    protected static ?int $sort = 8;

    protected int | string | array $columnSpan = 'full';

    protected string $view = 'filament.admin.widgets.top-listings-widget';

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
            ->query(fn (): Builder => $this->getTopListingsQuery())
            ->heading('أفضل الإعلانات أداءً')
            ->description('مرتبة حسب العروض، ثم CTR (هاتف ÷ مشاهدات)، ثم المشاهدات')
            ->columns([
                TextColumn::make('title')
                    ->label('عنوان الإعلان')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn (Listing $record): ?string => $record->title),

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
            ->defaultSort('offers_count', 'desc');
    }

    protected function getTopListingsQuery(): Builder
    {
        $startDate = $this->resolveFilterStartDate();
        $aggregated = $this->buildAggregatedListingsSubquery($startDate);

        return Listing::query()
            ->fromSub($aggregated, 'listings')
            ->select([
                'listings.id',
                'listings.title',
                'listings.views_count',
                'listings.phone_clicks_count',
                'listings.whatsapp_clicks_count',
                'listings.offers_count',
            ])
            ->selectRaw(
                'CASE WHEN listings.views_count > 0
                    THEN (listings.phone_clicks_count * 100.0 / listings.views_count)
                    ELSE 0
                END AS ctr'
            )
            ->orderByDesc('listings.offers_count')
            ->orderByDesc('ctr')
            ->orderByDesc('listings.views_count')
            ->limit(10);
    }

    protected function buildAggregatedListingsSubquery(Carbon $startDate): Builder
    {
        return Listing::query()
            ->select([
                'listings.id',
                'listings.title',
            ])
            ->selectSub(
                ListingView::query()
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('listing_views.listing_id', 'listings.id')
                    ->where('listing_views.created_at', '>=', $startDate),
                'views_count'
            )
            ->selectSub(
                ListingPhoneClick::query()
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('listing_phone_clicks.listing_id', 'listings.id')
                    ->where('listing_phone_clicks.created_at', '>=', $startDate),
                'phone_clicks_count'
            )
            ->selectSub(
                ListingWhatsappClick::query()
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('listing_whatsapp_clicks.listing_id', 'listings.id')
                    ->where('listing_whatsapp_clicks.created_at', '>=', $startDate),
                'whatsapp_clicks_count'
            )
            ->selectSub(
                Offer::query()
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('offers.listing_id', 'listings.id')
                    ->where('offers.created_at', '>=', $startDate),
                'offers_count'
            )
            ->where(function (Builder $query) use ($startDate): void {
                $query->whereHas('views', fn (Builder $q) => $q->where('created_at', '>=', $startDate))
                    ->orWhereHas('phoneClicks', fn (Builder $q) => $q->where('created_at', '>=', $startDate))
                    ->orWhereHas('whatsappClicks', fn (Builder $q) => $q->where('created_at', '>=', $startDate))
                    ->orWhereExists(function ($q) use ($startDate): void {
                        $q->selectRaw('1')
                            ->from('offers')
                            ->whereColumn('offers.listing_id', 'listings.id')
                            ->where('offers.created_at', '>=', $startDate);
                    });
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
