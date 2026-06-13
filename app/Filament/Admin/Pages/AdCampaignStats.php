<?php

namespace App\Filament\Admin\Pages;

use App\Models\AdCampaign;
use App\Models\AdCampaignLog;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class AdCampaignStats extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string|UnitEnum|null $navigationGroup = 'الحملات الإعلانية';

    protected static ?string $navigationLabel = 'الإحصائيات التفصيلية';

    protected static ?string $title = 'الإحصائيات التفصيلية';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.admin.pages.ad-campaign-stats';

    /**
     * @var array<string, mixed>
     */
    public array $filterData = [];

    public function mount(): void
    {
        $this->filterForm->fill([
            'startDate' => now()->subDays(6)->toDateString(),
            'endDate'   => now()->toDateString(),
        ]);
    }

    public function filterForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('startDate')
                    ->label('من تاريخ')
                    ->live()
                    ->required(),

                DatePicker::make('endDate')
                    ->label('إلى تاريخ')
                    ->live()
                    ->required()
                    ->minDate(fn (): ?string => $this->filterData['startDate'] ?? null),
            ])
            ->columns(2);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('تصدير البيانات')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(fn (): StreamedResponse => $this->exportCampaigns()),
        ];
    }

    public function getTotalViews(): int
    {
        return (int) AdCampaign::query()->sum('views_count');
    }

    public function getTotalClicks(): int
    {
        return (int) AdCampaign::query()->sum('clicks_count');
    }

    public function getAverageCtr(): string
    {
        $views  = $this->getTotalViews();
        $clicks = $this->getTotalClicks();

        if ($views === 0) {
            return '0%';
        }

        return number_format(($clicks / $views) * 100, 2) . '%';
    }

    /**
     * @return array{labels: array<int, string>, data: array<int, int>}
     */
    public function getImpressionsChartData(): array
    {
        $startDate = now()->subDays(29)->startOfDay();

        $countsByDay = AdCampaignLog::query()
            ->where('event_type', 'impression')
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as day')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day');

        $labels = [];
        $data   = [];

        for ($i = 29; $i >= 0; $i--) {
            $date     = Carbon::now()->subDays($i);
            $dayKey   = $date->toDateString();
            $labels[] = $date->translatedFormat('d/m');
            $data[]   = (int) ($countsByDay[$dayKey] ?? 0);
        }

        return [
            'labels' => $labels,
            'data'   => $data,
        ];
    }

    public function getTopCampaigns(): Collection
    {
        return AdCampaign::query()
            ->orderByDesc('clicks_count')
            ->limit(10)
            ->get(['id', 'title', 'views_count', 'clicks_count']);
    }

    public function exportCampaigns(): StreamedResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        if (class_exists(\Maatwebsite\Excel\Facades\Excel::class)) {
            return \Maatwebsite\Excel\Facades\Excel::download(
                new class implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings
                {
                    public function collection(): Collection
                    {
                        return AdCampaign::query()
                            ->orderByDesc('clicks_count')
                            ->get()
                            ->map(fn (AdCampaign $campaign): array => [
                                $campaign->id,
                                $campaign->title,
                                $campaign->placement,
                                $campaign->status,
                                $campaign->views_count,
                                $campaign->clicks_count,
                                $campaign->ctr,
                                $campaign->priority,
                                $campaign->starts_at?->toDateTimeString(),
                                $campaign->ends_at?->toDateTimeString(),
                            ]);
                    }

                    public function headings(): array
                    {
                        return [
                            'id',
                            'title',
                            'placement',
                            'status',
                            'views_count',
                            'clicks_count',
                            'ctr',
                            'priority',
                            'starts_at',
                            'ends_at',
                        ];
                    }
                },
                'ad-campaigns-' . now()->format('Y-m-d') . '.xlsx'
            );
        }

        $filename = 'ad-campaigns-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'id',
                'title',
                'placement',
                'status',
                'views_count',
                'clicks_count',
                'ctr',
                'priority',
                'starts_at',
                'ends_at',
            ]);

            AdCampaign::query()
                ->orderByDesc('clicks_count')
                ->chunk(100, function ($campaigns) use ($handle): void {
                    foreach ($campaigns as $campaign) {
                        fputcsv($handle, [
                            $campaign->id,
                            $campaign->title,
                            $campaign->placement,
                            $campaign->status,
                            $campaign->views_count,
                            $campaign->clicks_count,
                            $campaign->ctr,
                            $campaign->priority,
                            $campaign->starts_at?->toDateTimeString(),
                            $campaign->ends_at?->toDateTimeString(),
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
