<?php

/**
 * Generate PDF from combined Nilex report markdown.
 * Usage: php scripts/generate_full_report_pdf.php
 */

declare(strict_types=1);

use League\CommonMark\CommonMarkConverter;

require __DIR__ . '/../vendor/autoload.php';

$baseDir  = dirname(__DIR__);
$part1    = $baseDir . '/docs/reports/comprehensive_implementation_report_june_11_12_2026.md';
$part2    = $baseDir . '/docs/reports/comprehensive_report_expert_review.md';
$htmlPath = $baseDir . '/docs/reports/nilex_full_report_june_11_12_combined.html';
$pdfPath  = $baseDir . '/docs/reports/nilex_full_report_june_11_12_combined.pdf';
$cssPath  = $baseDir . '/docs/reports/pdf-styles.css';

foreach ([$part1, $part2] as $path) {
    if (! is_file($path)) {
        fwrite(STDERR, "Missing: {$path}\n");
        exit(1);
    }
}

$readUtf8 = static function (string $path): string {
    $content = file_get_contents($path);
    $content = preg_replace('/^\xEF\xBB\xBF/u', '', $content) ?? $content;

    return $content;
};

$cover = <<<'MD'
# Nilex Marketplace — التقرير الكامل
## التنفيذ الشامل + المراجعة الخبيرة | 11–12 يونيو 2026

**المشروع:** Nilex Marketplace  
**التقنيات:** Laravel 13 · Filament v5.4 · Livewire · Pest  
**حالة الاختبارات:** 240 اختبارًا ناجحًا (727 assertion)  
**تاريخ الإصدار:** 12 يونيو 2026

**محتويات الوثيقة:**
1. **الجزء الأول** — تقرير شامل لكل ما تم تنفيذه (11–12 يونيو)
2. **الجزء الثاني** — مراجعة خبيرة: ما الذي ينقص للـ production readiness

<div class="page-break"></div>

<div class="part-title">الجزء الأول — التقرير الشامل للتنفيذ</div>

MD;

$part1Body = preg_replace('/^# .+\R## .+\R\R/u', '', $readUtf8($part1), 1) ?? $readUtf8($part1);

$part2Header = <<<'MD'

<div class="page-break"></div>

<div class="part-title">الجزء الثاني — المراجعة الخبيرة (Production Readiness)</div>

MD;

$part2Body = preg_replace('/^# .+\R\R/u', '', $readUtf8($part2), 1) ?? $readUtf8($part2);

$markdown = $cover . $part1Body . $part2Header . $part2Body;
$css      = is_file($cssPath) ? file_get_contents($cssPath) : '';

$converter = new CommonMarkConverter([
    'html_input'         => 'allow',
    'allow_unsafe_links' => false,
]);

$body = $converter->convert($markdown)->getContent();

$html = <<<HTML
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>Nilex — التقرير الكامل 11–12 يونيو 2026</title>
    <style>
    {$css}
    </style>
</head>
<body>
{$body}
</body>
</html>
HTML;

file_put_contents($htmlPath, $html);
echo "HTML written: {$htmlPath}\n";

$edgeCandidates = [
    'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
    'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
    'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
    'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
];

$browser = null;
foreach ($edgeCandidates as $candidate) {
    if (is_file($candidate)) {
        $browser = $candidate;
        break;
    }
}

if ($browser === null) {
    fwrite(STDERR, "No headless browser found. Open HTML manually and Print to PDF:\n{$htmlPath}\n");
    exit(2);
}

@unlink($pdfPath);

$fileUrl = 'file:///' . str_replace(['\\', ' '], ['/', '%20'], $htmlPath);

$cmd = sprintf(
    '"%s" --headless=new --disable-gpu --no-sandbox --print-to-pdf="%s" --print-to-pdf-no-header "%s"',
    $browser,
    $pdfPath,
    $fileUrl
);

exec($cmd, $output, $exitCode);

if ($exitCode !== 0 || ! is_file($pdfPath)) {
    fwrite(STDERR, "PDF generation failed (exit {$exitCode}).\n");
    fwrite(STDERR, "Fallback: open {$htmlPath} → Print → Save as PDF\n");
    exit(3);
}

echo "PDF written: {$pdfPath}\n";
echo 'Size: ' . number_format(filesize($pdfPath) / 1024, 1) . " KB\n";
