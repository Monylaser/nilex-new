<?php

namespace App\Services;

use App\Models\PointTransaction;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

class MonthlyReportService
{
    public function __construct(
        private SellerListingAnalyticsService $analytics,
    ) {}

    public function generateForUser(User $user): string
    {
        $stats = $this->analytics->getLastMonthStats($user);
        $stats['listings_count'] = $user->listings()->count();
        $stats['conversion_rate'] = $this->analytics->getConversionRate($user);
        $stats['points_spent'] = $this->getBoostPointsSpent($user);

        $html = View::make('reports.monthly-performance', [
            'user'  => $user,
            'stats' => $stats,
        ])->render();

        $pdfContent = $this->buildSimplePdf($stats, $user);

        $directory = "reports/{$user->id}";
        Storage::disk('local')->makeDirectory($directory);

        $filename = now()->subMonth()->format('Y-m') . '-report.pdf';
        $path     = "{$directory}/{$filename}";

        Storage::disk('local')->put($path, $pdfContent);
        Storage::disk('local')->put("{$directory}/" . now()->subMonth()->format('Y-m') . '-report.html', $html);

        return $path;
    }

    public function getBoostPointsSpent(User $user, ?\DateTimeInterface $from = null, ?\DateTimeInterface $to = null): int
    {
        $query = PointTransaction::query()
            ->where('user_id', $user->id)
            ->where('amount', '<', 0);

        if ($from !== null) {
            $query->where('created_at', '>=', $from);
        }

        if ($to !== null) {
            $query->where('created_at', '<=', $to);
        }

        return (int) abs($query->sum('amount'));
    }

    private function buildSimplePdf(array $stats, User $user): string
    {
        $lines = [
            'Nilex Monthly Performance Report',
            'Seller: ' . $user->name,
            'Period: ' . ($stats['period_label'] ?? ''),
            'Views: ' . ($stats['views'] ?? 0),
            'Phone Clicks: ' . ($stats['phone_clicks'] ?? 0),
            'WhatsApp Clicks: ' . ($stats['whatsapp_clicks'] ?? 0),
            'Conversion Rate: ' . ($stats['conversion_rate'] ?? 0) . '%',
            'Points Spent on Boosts: ' . ($stats['points_spent'] ?? 0),
        ];

        $content = "BT\n/F1 11 Tf\n";
        $y       = 750;

        foreach ($lines as $line) {
            $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $line);
            $content .= "50 {$y} Td\n({$escaped}) Tj\n0 -18 Td\n";
            $y -= 18;
        }

        $content .= 'ET';
        $len = strlen($content);

        return "%PDF-1.4\n"
            . "1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n"
            . "2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n"
            . "3 0 obj<</Type/Page/MediaBox[0 0 612 792]/Contents 4 0 R/Resources<</Font<</F1 5 0 R>>>>/Parent 2 0 R>>endobj\n"
            . "4 0 obj<</Length {$len}>>stream\n{$content}\nendstream endobj\n"
            . "5 0 obj<</Type/Font/Subtype/Type1/BaseFont/Helvetica>>endobj\n"
            . "xref\n0 6\n"
            . "0000000000 65535 f \n"
            . "0000000009 00000 n \n"
            . "0000000052 00000 n \n"
            . "0000000101 00000 n \n"
            . "0000000240 00000 n \n"
            . "0000000350 00000 n \n"
            . "trailer<</Size 6/Root 1 0 R>>\n"
            . "startxref\n400\n%%EOF";
    }
}
