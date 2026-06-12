<?php

namespace App\Filament\Admin\Widgets;

use App\Models\ListingPhoneClick;
use App\Models\ListingView;
use App\Models\ListingWhatsappClick;
use App\Models\Offer;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;

class LeadFunnelWidget extends BaseWidget
{
    protected static ?int $sort = 7;

    protected ?string $heading = 'قمع العملاء المحتملين';

    protected ?string $description = 'المشاهدات ← الهاتف ← الواتساب ← العروض';

    public ?string $filter = 'last_7_days';

    public static function canView(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public function updatedFilter(): void
    {
        $this->cachedStats = null;
    }

    /**
     * @return array<scalar, scalar>|null
     */
    protected function getFilters(): ?array
    {
        return [
            'today'        => 'اليوم',
            'last_7_days'  => 'آخر 7 أيام',
            'last_30_days' => 'آخر 30 يوم',
        ];
    }

    public function getSectionContentComponent(): Component
    {
        $section = Section::make()
            ->heading($this->getHeading())
            ->description($this->getDescription())
            ->schema($this->getCachedStats())
            ->columns($this->getColumns())
            ->contained(false)
            ->gridContainer();

        if ($this->getFilters()) {
            $section->afterHeader(new HtmlString(
                view('filament.admin.widgets.components.date-range-filter', [
                    'filters' => $this->getFilters(),
                ])->render(),
            ));
        }

        return $section;
    }

    protected function getStats(): array
    {
        $startDate = $this->resolveFilterStartDate();

        $totalViews = (int) ListingView::query()
            ->where('created_at', '>=', $startDate)
            ->count();

        $totalPhoneClicks = (int) ListingPhoneClick::query()
            ->where('created_at', '>=', $startDate)
            ->count();

        $totalWhatsappClicks = (int) ListingWhatsappClick::query()
            ->where('created_at', '>=', $startDate)
            ->count();

        $totalOffers = (int) Offer::query()
            ->where('created_at', '>=', $startDate)
            ->count();

        $viewToPhoneCtr       = $this->calculateCtr($totalPhoneClicks, $totalViews);
        $phoneToWhatsappCtr   = $this->calculateCtr($totalWhatsappClicks, $totalPhoneClicks);
        $whatsappToOfferCtr   = $this->calculateCtr($totalOffers, $totalWhatsappClicks);

        return [
            Stat::make('إجمالي المشاهدات', number_format($totalViews))
                ->description('خطوة القمع الأولى')
                ->descriptionIcon('heroicon-m-eye')
                ->color('info'),

            Stat::make('نقرات الهاتف', number_format($totalPhoneClicks))
                ->description('CTR من المشاهدات: ' . $this->formatPercent($viewToPhoneCtr))
                ->descriptionIcon('heroicon-m-phone')
                ->color('primary'),

            Stat::make('نقرات الواتساب', number_format($totalWhatsappClicks))
                ->description('CTR من الهاتف: ' . $this->formatPercent($phoneToWhatsappCtr))
                ->descriptionIcon('heroicon-m-chat-bubble-left-right')
                ->color('warning'),

            Stat::make('إجمالي العروض', number_format($totalOffers))
                ->description('CTR من الواتساب: ' . $this->formatPercent($whatsappToOfferCtr))
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),

            Stat::make('CTR مشاهدة → هاتف', $this->formatPercent($viewToPhoneCtr))
                ->description('نقرات الهاتف ÷ المشاهدات × 100')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color($viewToPhoneCtr >= 5 ? 'success' : 'gray'),

            Stat::make('CTR هاتف → واتساب', $this->formatPercent($phoneToWhatsappCtr))
                ->description('نقرات الواتساب ÷ نقرات الهاتف × 100')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color($phoneToWhatsappCtr >= 5 ? 'success' : 'gray'),

            Stat::make('CTR واتساب → عرض', $this->formatPercent($whatsappToOfferCtr))
                ->description('العروض ÷ نقرات الواتساب × 100')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color($whatsappToOfferCtr >= 5 ? 'success' : 'gray'),
        ];
    }

    private function resolveFilterStartDate(): Carbon
    {
        return match ($this->filter) {
            'today'        => Carbon::today(),
            'last_30_days' => Carbon::now()->subDays(29)->startOfDay(),
            default        => Carbon::now()->subDays(6)->startOfDay(),
        };
    }

    private function calculateCtr(int $numerator, int $denominator): float
    {
        if ($denominator <= 0) {
            return 0.0;
        }

        return ($numerator / $denominator) * 100;
    }

    private function formatPercent(float $value): string
    {
        return number_format($value, 2) . '%';
    }
}
