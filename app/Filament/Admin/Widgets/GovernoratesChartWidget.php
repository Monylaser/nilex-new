<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Listing;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class GovernoratesChartWidget extends ChartWidget
{
    protected ?string $heading = 'أكثر 5 محافظات نشاطاً';

    protected static ?int $sort = 3;

    public static function canView(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    protected function getData(): array
    {
        $rows = Listing::query()
            ->select('locations.name_ar', DB::raw('COUNT(listings.id) as total'))
            ->join('locations', 'listings.province_id', '=', 'locations.id')
            ->where('listings.status', 'published')
            ->groupBy('locations.id', 'locations.name_ar')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $labels = $rows->pluck('name_ar')->toArray();
        $data   = $rows->pluck('total')->toArray();

        $colors = [
            '#8b5cf6', // violet-500
            '#a78bfa', // violet-400
            '#c4b5fd', // violet-300
            '#7c3aed', // violet-600
            '#6d28d9', // violet-700
        ];

        return [
            'datasets' => [
                [
                    'label'           => 'الإعلانات المنشورة',
                    'data'            => $data,
                    'backgroundColor' => array_slice($colors, 0, count($data)),
                    'borderColor'     => '#ffffff',
                    'borderWidth'     => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
