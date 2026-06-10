<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Listing;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class CategoriesChartWidget extends ChartWidget
{
    protected ?string $heading = 'أكثر 5 أقسام نشاطاً';

    protected static ?int $sort = 4;

    public static function canView(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    protected function getData(): array
    {
        $rows = Listing::query()
            ->select('categories.name_ar', DB::raw('COUNT(listings.id) as total'))
            ->join('categories', 'listings.category_id', '=', 'categories.id')
            ->where('listings.status', 'published')
            ->groupBy('categories.id', 'categories.name_ar')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $labels = $rows->pluck('name_ar')->toArray();
        $data   = $rows->pluck('total')->toArray();

        $backgroundColors = [
            '#8b5cf6', // violet
            '#ec4899', // pink
            '#f59e0b', // amber
            '#10b981', // emerald
            '#3b82f6', // blue
        ];

        return [
            'datasets' => [
                [
                    'data'            => $data,
                    'backgroundColor' => array_slice($backgroundColors, 0, count($data)),
                    'hoverOffset'     => 6,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
