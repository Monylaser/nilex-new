<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Transaction;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class BestSellingPlansChart extends ChartWidget
{
    protected ?string $heading = 'أكثر 5 باقات مبيعاً';

    protected static ?int $sort = 5;

    protected ?string $maxHeight = '320px';

    public static function canView(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    protected function getData(): array
    {
        $rows = Transaction::query()
            ->select([
                'point_plans.name_ar',
                DB::raw('COUNT(transactions.id) as purchase_count'),
                DB::raw('SUM(transactions.amount) as total_revenue'),
            ])
            ->join('point_plans', 'transactions.plan_id', '=', 'point_plans.id')
            ->where('transactions.status', 'completed')
            ->groupBy('point_plans.id', 'point_plans.name_ar')
            ->orderByDesc('purchase_count')
            ->limit(5)
            ->get();

        $labels = $rows->map(
            fn ($row) => sprintf(
                '%s (%s ج.م)',
                $row->name_ar,
                number_format((float) $row->total_revenue, 0),
            ),
        )->all();

        $purchaseCounts = $rows->pluck('purchase_count')->map(fn ($count) => (int) $count)->all();

        return [
            'datasets' => [
                [
                    'label'           => 'عدد المشتريات',
                    'data'            => $purchaseCounts,
                    'backgroundColor' => '#1D9E75',
                    'borderColor'     => '#178a66',
                    'borderWidth'     => 1,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
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
                    'ticks'       => [
                        'precision' => 0,
                    ],
                ],
            ],
        ];
    }
}
