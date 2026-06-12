<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Transaction;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class WeeklyRevenueChart extends ChartWidget
{
    protected ?string $heading = 'الإيرادات (آخر 7 أيام)';

    protected static ?int $sort = 4;

    protected ?string $maxHeight = '320px';

    public static function canView(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    protected function getData(): array
    {
        $startDate = Carbon::now()->subDays(6)->startOfDay();

        $revenuesByDay = Transaction::query()
            ->completed()
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as day')
            ->selectRaw('COALESCE(SUM(amount), 0) as revenue')
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('revenue', 'day');

        $labels = [];
        $data   = [];

        for ($i = 6; $i >= 0; $i--) {
            $date     = Carbon::now()->subDays($i);
            $dayKey   = $date->toDateString();
            $labels[] = $date->translatedFormat('D (d/m)');
            $data[]   = round((float) ($revenuesByDay[$dayKey] ?? 0), 2);
        }

        return [
            'datasets' => [
                [
                    'label'           => 'الإيرادات (ج.م)',
                    'data'            => $data,
                    'backgroundColor' => 'rgba(139, 92, 246, 0.2)',
                    'borderColor'     => '#8b5cf6',
                    'borderWidth'     => 2,
                    'fill'            => true,
                    'tension'         => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
        ];
    }
}
