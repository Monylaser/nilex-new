<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Listing;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class ListingsChart extends ChartWidget
{
    protected ?string $heading = 'معدل نشر الإعلانات (آخر 7 أيام)';
    protected static ?int $sort = 2; // هيظهر تحت كروت الإحصائيات

    protected function getData(): array
    {
        $data = [];
        $labels = [];

        // جلب إحصائيات آخر 7 أيام
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $labels[] = $date->translatedFormat('D (d/m)'); // مثال: السبت (15/05)
            
            $count = Listing::whereDate('created_at', $date->toDateString())->count();
            $data[] = $count;
        }

        return [
            'datasets' => [
                [
                    'label' => 'إعلانات جديدة',
                    'data' => $data,
                    'backgroundColor' => '#8b5cf6', // لون متناسق مع ثيم Violet
                    'borderColor' => '#8b5cf6',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line'; // نوع الرسم البياني: خطي
    }
}