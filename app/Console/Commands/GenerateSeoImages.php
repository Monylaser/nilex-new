<?php

namespace App\Console\Commands;

use App\Models\Location;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class GenerateSeoImages extends Command
{
    protected $signature   = 'nilex:generate-seo-images {--force : Overwrite existing images}';
    protected $description = 'توليد صور OG (1200×630) لجميع محافظات مصر باستخدام GD Library';

    // ألوان التدرج: #1D9E75 → #085041
    private const GRADIENT_START = ['r' => 0x1D, 'g' => 0x9E, 'b' => 0x75];
    private const GRADIENT_END   = ['r' => 0x08, 'g' => 0x50, 'b' => 0x41];

    private const IMAGE_WIDTH  = 1200;
    private const IMAGE_HEIGHT = 630;
    private const FONT_SIZE_GOV = 60;
    private const FONT_SIZE_BRAND = 30;
    private const FONT_SIZE_SUB = 24;

    public function handle(): int
    {
        // التحقق من توافر مكتبة GD
        if (! extension_loaded('gd')) {
            $this->error('❌ مكتبة GD غير مفعّلة. يرجى تفعيل php_gd في php.ini');
            return Command::FAILURE;
        }

        // إعداد المسارات
        $outputDir = public_path('images/seo/governorates');
        $fontsDir  = public_path('fonts');

        // إنشاء المجلدات إذا لم تكن موجودة
        File::ensureDirectoryExists($outputDir, 0755, true);
        File::ensureDirectoryExists($fontsDir, 0755, true);

        // تحديد مسار الخط العربي
        $fontPath = $this->resolveFontPath($fontsDir);

        if (! $fontPath) {
            $this->warn('⚠️  خط Cairo العربي غير موجود. يتم التخطي إلى الوضع البديل (بدون نص عربي مشكّل).');
            $this->warn('   💡 ضع الخط في: ' . $fontsDir . '/Cairo-Bold.ttf');
        }

        // جلب جميع المحافظات من قاعدة البيانات
        $governorates = Location::where('level', Location::LEVEL_GOVERNORATE)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        if ($governorates->isEmpty()) {
            $this->error('❌ لا توجد محافظات في قاعدة البيانات. شغّل أولاً: php artisan db:seed --class=LocationSeeder');
            return Command::FAILURE;
        }

        $this->info("🖼️  توليد {$governorates->count()} صورة SEO للمحافظات المصرية...");
        $this->newLine();

        $generated = 0;
        $skipped   = 0;

        $bar = $this->output->createProgressBar($governorates->count());
        $bar->start();

        foreach ($governorates as $governorate) {
            $slug      = $governorate->slug;
            $imagePath = $outputDir . DIRECTORY_SEPARATOR . $slug . '.jpg';

            // تخطي الصور الموجودة إن لم يكن خيار --force
            if (File::exists($imagePath) && ! $this->option('force')) {
                $skipped++;
                $bar->advance();
                continue;
            }

            $success = $this->generateImage(
                $imagePath,
                $governorate->name_ar,
                $governorate->name_en,
                $fontPath
            );

            if ($success) {
                $generated++;
            } else {
                $this->newLine();
                $this->warn("  ⚠️  فشل توليد صورة: {$governorate->name_ar}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("📊 إحصائيات توليد الصور:");
        $this->info("   ✅ تم توليد: {$generated} صورة");

        if ($skipped > 0) {
            $this->line("   ⏭️  تم تخطي (موجودة): {$skipped} صورة  → استخدم --force للكتابة فوقها");
        }

        $this->newLine();
        $this->info("📂 مسار الحفظ: " . $outputDir);

        return Command::SUCCESS;
    }

    /**
     * توليد صورة واحدة 1200×630 للمحافظة.
     */
    private function generateImage(string $outputPath, string $nameAr, string $nameEn, ?string $fontPath): bool
    {
        try {
            // إنشاء الصورة
            $image = imagecreatetruecolor(self::IMAGE_WIDTH, self::IMAGE_HEIGHT);

            if (! $image) {
                return false;
            }

            // رسم تدرج الخلفية (أعمدة عمودية)
            $this->drawGradient($image);

            // رسم طبقة شفافة داكنة في الأسفل لتحسين القراءة
            $this->drawOverlay($image);

            // رسم النصوص
            if ($fontPath && File::exists($fontPath)) {
                $this->drawTextWithFont($image, $nameAr, $nameEn, $fontPath);
            } else {
                $this->drawTextBuiltin($image, $nameEn);
            }

            // رسم شعار وخط زخرفي
            $this->drawDecorations($image, $fontPath);

            // حفظ الصورة بجودة عالية
            imagejpeg($image, $outputPath, 92);
            imagedestroy($image);

            return true;

        } catch (\Throwable $e) {
            $this->warn("  خطأ في توليد الصورة: " . $e->getMessage());
            return false;
        }
    }

    /**
     * رسم تدرج لوني من اليسار إلى اليمين.
     */
    private function drawGradient($image): void
    {
        $start = self::GRADIENT_START;
        $end   = self::GRADIENT_END;

        for ($x = 0; $x < self::IMAGE_WIDTH; $x++) {
            $ratio = $x / self::IMAGE_WIDTH;

            $r = (int) ($start['r'] + ($end['r'] - $start['r']) * $ratio);
            $g = (int) ($start['g'] + ($end['g'] - $start['g']) * $ratio);
            $b = (int) ($start['b'] + ($end['b'] - $start['b']) * $ratio);

            $color = imagecolorallocate($image, $r, $g, $b);
            imageline($image, $x, 0, $x, self::IMAGE_HEIGHT, $color);
        }
    }

    /**
     * رسم طبقة شبكية زخرفية خفيفة على الخلفية.
     */
    private function drawOverlay($image): void
    {
        $overlayColor = imagecolorallocatealpha($image, 0, 0, 0, 80);

        // مستطيل داكن في الأسفل لتحسين قراءة النص
        imagefilledrectangle(
            $image,
            0,
            self::IMAGE_HEIGHT - 120,
            self::IMAGE_WIDTH,
            self::IMAGE_HEIGHT,
            imagecolorallocatealpha($image, 0, 0, 0, 60)
        );

        // خط زخرفي أعلى الصورة
        $accentColor = imagecolorallocate($image, 255, 255, 255);
        imagesetthickness($image, 3);
        imageline($image, 60, 50, 260, 50, $accentColor);
        imagesetthickness($image, 1);
    }

    /**
     * رسم النصوص باستخدام خط TrueType (Cairo Arabic).
     *
     * ملاحظة: GD + FreeType يدعمان Unicode لكن لا يُجري شكلنة العربية (Arabic shaping)
     * تلقائياً. إذا ظهر النص غير متصل، استخدم مكتبة php-arabic أو الـ Intl extension.
     */
    private function drawTextWithFont($image, string $nameAr, string $nameEn, string $fontPath): void
    {
        $white     = imagecolorallocate($image, 255, 255, 255);
        $lightGray = imagecolorallocate($image, 220, 240, 230);

        // اسم المحافظة بالعربي - في المركز
        $arText = $this->prepareArabicText($nameAr);
        $this->drawCenteredText($image, $fontPath, self::FONT_SIZE_GOV, $arText, $white, self::IMAGE_HEIGHT / 2 - 20);

        // اسم المحافظة بالإنجليزي - أسفل الاسم العربي
        $this->drawCenteredText($image, $fontPath, self::FONT_SIZE_SUB, $nameEn, $lightGray, self::IMAGE_HEIGHT / 2 + 55);

        // نص "Egypt • مصر" في الأسفل
        $this->drawCenteredText($image, $fontPath, 20, 'Egypt  •  مصر', $lightGray, self::IMAGE_HEIGHT - 55);

        // شعار Nilex في الركن العلوي الأيسر (RTL: يظهر في الأيسر بصرياً لكن مرجعه الأيمن)
        $this->drawBrandText($image, $fontPath, $white, $lightGray);
    }

    /**
     * رسم النصوص باستخدام خط PHP المدمج (احتياطي بدون دعم عربي).
     */
    private function drawTextBuiltin($image, string $nameEn): void
    {
        $white = imagecolorallocate($image, 255, 255, 255);

        // حساب مركز النص
        $textWidth = strlen($nameEn) * 14;
        $x         = (self::IMAGE_WIDTH - $textWidth) / 2;

        imagestring($image, 5, (int) $x, self::IMAGE_HEIGHT / 2 - 20, $nameEn, $white);
        imagestring($image, 3, 40, 30, 'Nilex', $white);
    }

    /**
     * رسم النص الموسوم لـ Nilex في الأعلى.
     */
    private function drawBrandText($image, string $fontPath, $white, $lightGray): void
    {
        // "Nilex" في الأعلى يمين
        $brandText = 'Nilex';
        $bbox      = imagettfbbox(self::FONT_SIZE_BRAND, 0, $fontPath, $brandText);
        $textWidth = abs($bbox[4] - $bbox[0]);
        $x         = self::IMAGE_WIDTH - $textWidth - 60;
        $y         = 55;

        imagettftext($image, self::FONT_SIZE_BRAND, 0, (int) $x, (int) $y, $white, $fontPath, $brandText);

        // خط زخرفي تحت النص
        $lineY = $y + 8;
        imagesetthickness($image, 2);
        imageline($image, (int) $x, (int) $lineY, (int) ($x + $textWidth), (int) $lineY, $lightGray);
        imagesetthickness($image, 1);

        // نقطة فاصلة
        $dotColor = imagecolorallocate($image, 0xF0, 0xAD, 0x4E);
        imagefilledellipse($image, (int) ($x + $textWidth + 10), (int) ($y - 10), 10, 10, $dotColor);
    }

    /**
     * رسم زخارف إضافية على الصورة.
     */
    private function drawDecorations($image, ?string $fontPath): void
    {
        $white       = imagecolorallocate($image, 255, 255, 255);
        $accentColor = imagecolorallocatealpha($image, 255, 255, 255, 100);

        // دوائر زخرفية في الأركان
        imagearc($image, -30, -30, 200, 200, 0, 360, $accentColor);
        imagearc($image, self::IMAGE_WIDTH + 30, self::IMAGE_HEIGHT + 30, 200, 200, 0, 360, $accentColor);

        // خط سفلي فاصل
        $goldColor = imagecolorallocate($image, 0xF0, 0xAD, 0x4E);
        imagesetthickness($image, 4);
        imageline($image, 0, self::IMAGE_HEIGHT - 8, self::IMAGE_WIDTH, self::IMAGE_HEIGHT - 8, $goldColor);
        imagesetthickness($image, 1);
    }

    /**
     * رسم نص في المنتصف أفقياً.
     */
    private function drawCenteredText($image, string $fontPath, int $fontSize, string $text, $color, float $y): void
    {
        $bbox      = imagettfbbox($fontSize, 0, $fontPath, $text);
        $textWidth = abs($bbox[4] - $bbox[0]);
        $x         = (self::IMAGE_WIDTH - $textWidth) / 2;

        imagettftext($image, $fontSize, 0, (int) $x, (int) $y, $color, $fontPath, $text);
    }

    /**
     * تجهيز النص العربي لـ GD.
     * GD لا يُجري Arabic shaping تلقائياً، لكن العديد من الخطوط الحديثة
     * (كـ Cairo) تحتوي على الأشكال كاملة وتظهر بشكل صحيح مع FreeType.
     */
    private function prepareArabicText(string $text): string
    {
        // تأكد من الترميز الصحيح
        if (! mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'auto');
        }

        return $text;
    }

    /**
     * تحديد مسار الخط العربي المتاح.
     */
    private function resolveFontPath(string $fontsDir): ?string
    {
        $candidates = [
            $fontsDir . '/Cairo-Bold.ttf',
            $fontsDir . '/Cairo-VariableFont_slnt,wght.ttf',
            $fontsDir . '/cairo.ttf',
            $fontsDir . '/Cairo.ttf',
        ];

        foreach ($candidates as $path) {
            if (File::exists($path)) {
                $this->line("  🔤 استخدام الخط: " . basename($path));
                return $path;
            }
        }

        return null;
    }
}
