<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تقرير الأداء الشهري — {{ $stats['period_label'] ?? '' }}</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #fafafa; color: #18181b; padding: 2rem; }
        .card { background: #fff; border: 1px solid #e4e4e7; border-radius: 16px; padding: 2rem; max-width: 640px; margin: 0 auto; }
        h1 { color: #1D9E75; font-size: 1.5rem; margin-bottom: 0.5rem; }
        .meta { color: #71717a; font-size: 0.875rem; margin-bottom: 1.5rem; }
        .grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; }
        .stat { background: #f4f4f5; border-radius: 12px; padding: 1rem; text-align: center; }
        .stat-label { font-size: 0.75rem; color: #71717a; }
        .stat-value { font-size: 1.5rem; font-weight: 800; color: #18181b; }
    </style>
</head>
<body>
    <div class="card">
        <h1>تقرير الأداء الشهري</h1>
        <p class="meta">{{ $user->name }} — {{ $stats['period_label'] ?? '' }}</p>
        <div class="grid">
            <div class="stat">
                <div class="stat-label">مشاهدات</div>
                <div class="stat-value">{{ number_format($stats['views'] ?? 0) }}</div>
            </div>
            <div class="stat">
                <div class="stat-label">نقرات الهاتف</div>
                <div class="stat-value">{{ number_format($stats['phone_clicks'] ?? 0) }}</div>
            </div>
            <div class="stat">
                <div class="stat-label">نقرات الواتساب</div>
                <div class="stat-value">{{ number_format($stats['whatsapp_clicks'] ?? 0) }}</div>
            </div>
            <div class="stat">
                <div class="stat-label">معدل التحويل</div>
                <div class="stat-value">{{ $stats['conversion_rate'] ?? 0 }}%</div>
            </div>
            <div class="stat">
                <div class="stat-label">نقاط التمييز</div>
                <div class="stat-value">{{ number_format($stats['points_spent'] ?? 0) }}</div>
            </div>
            <div class="stat">
                <div class="stat-label">عدد الإعلانات</div>
                <div class="stat-value">{{ number_format($stats['listings_count'] ?? 0) }}</div>
            </div>
        </div>
    </div>
</body>
</html>
