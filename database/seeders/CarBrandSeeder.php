<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CarBrand;
use App\Models\CarModel;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

class CarBrandSeeder extends Seeder
{
    public function run(): void
    {
        // 1. تعطيل فحص العلاقات الخارجية لتجنب الـ Error
        Schema::disableForeignKeyConstraints();

        // 2. مسح البيانات القديمة بالترتيب الصحيح
        CarModel::truncate();
        CarBrand::truncate();

        // 3. إعادة تفعيل الفحص
        Schema::enableForeignKeyConstraints();

        $data = [
            ['ar' => 'تويوتا',      'en' => 'Toyota',      'models' => [
                'كورولا' => 'Corolla', 'كامري' => 'Camry', 'ياريس' => 'Yaris',
                'RAV4' => 'RAV4', 'فورتشنر' => 'Fortuner', 'هايلكس' => 'Hilux',
                'برادو' => 'Prado', 'لاند كروزر' => 'Land Cruiser', 'أفالون' => 'Avalon',
                'إينوفا' => 'Innova', 'راش' => 'Rush', 'C-HR' => 'C-HR',
            ]],
            ['ar' => 'هيونداي',     'en' => 'Hyundai',     'models' => [
                'إيلانترا' => 'Elantra', 'أكسنت' => 'Accent', 'توسان' => 'Tucson',
                'سوناتا' => 'Sonata', 'i10' => 'i10', 'i20' => 'i20', 'i30' => 'i30',
                'سانتافي' => 'Santa Fe', 'كريتا' => 'Creta', 'فيلوستر' => 'Veloster',
            ]],
            ['ar' => 'كيا',         'en' => 'Kia',         'models' => [
                'سيراتو' => 'Cerato', 'سبورتاج' => 'Sportage', 'ريو' => 'Rio',
                'سورينتو' => 'Sorento', 'بيكانتو' => 'Picanto', 'سيلتوس' => 'Seltos',
                'كارنيفال' => 'Carnival', 'K5' => 'K5', 'تيلورايد' => 'Telluride',
                'ستينجر' => 'Stinger',
            ]],
            ['ar' => 'نيسان',       'en' => 'Nissan',      'models' => [
                'صني' => 'Sunny', 'سنترا' => 'Sentra', 'تيدا' => 'Tiida',
                'إكس تريل' => 'X-Trail', 'باترول' => 'Patrol', 'نافارا' => 'Navara',
                'ألتيما' => 'Altima', 'أرمادا' => 'Armada', 'جوك' => 'Juke',
                'مكسيما' => 'Maxima', 'مورانو' => 'Murano',
            ]],
            ['ar' => 'شيفروليه',    'en' => 'Chevrolet',   'models' => [
                'أوبترا' => 'Optra', 'أفيو' => 'Aveo', 'كروز' => 'Cruze',
                'كابتيفا' => 'Captiva', 'سبارك' => 'Spark', 'ترافيرس' => 'Traverse',
                'إكينوكس' => 'Equinox', 'ماليبو' => 'Malibu', 'تاهو' => 'Tahoe',
                'سيلفرادو' => 'Silverado',
            ]],
            ['ar' => 'مرسيدس',      'en' => 'Mercedes-Benz','models' => [
                'C180' => 'C180', 'C200' => 'C200', 'C300' => 'C300',
                'E200' => 'E200', 'E300' => 'E300', 'E350' => 'E350',
                'S500' => 'S500', 'GLA' => 'GLA', 'GLC' => 'GLC',
                'GLE' => 'GLE', 'GLS' => 'GLS', 'A200' => 'A200',
                'CLA' => 'CLA', 'AMG GT' => 'AMG GT',
            ]],
            ['ar' => 'BMW',         'en' => 'BMW',         'models' => [
                '116' => '116', '118' => '118', '316' => '316', '318' => '318',
                '320' => '320', '520' => '520', '528' => '528', '730' => '730',
                '740' => '740', 'X1' => 'X1', 'X3' => 'X3', 'X5' => 'X5',
                'X6' => 'X6', 'M3' => 'M3', 'M5' => 'M5',
            ]],
            ['ar' => 'هوندا',       'en' => 'Honda',       'models' => [
                'سيفيك' => 'Civic', 'أكورد' => 'Accord', 'CR-V' => 'CR-V',
                'HR-V' => 'HR-V', 'جاز' => 'Jazz', 'بايلوت' => 'Pilot',
                'أوديسي' => 'Odyssey', 'BR-V' => 'BR-V', 'سيتي' => 'City',
            ]],
            ['ar' => 'فورد',        'en' => 'Ford',        'models' => [
                'فيستا' => 'Fiesta', 'فوكس' => 'Focus', 'فيوجن' => 'Fusion',
                'إيدج' => 'Edge', 'إكسبلورر' => 'Explorer', 'موستانج' => 'Mustang',
                'رينجر' => 'Ranger', 'F-150' => 'F-150', 'إيكو سبورت' => 'EcoSport',
            ]],
            ['ar' => 'أوبل',        'en' => 'Opel',        'models' => [
                'أسترا' => 'Astra', 'كورسا' => 'Corsa', 'فيكترا' => 'Vectra',
                'زافيرا' => 'Zafira', 'موكا' => 'Mokka', 'إينسيجنيا' => 'Insignia',
                'أنتارا' => 'Antara', 'مييفا' => 'Meriva',
            ]],
            ['ar' => 'بيجو',        'en' => 'Peugeot',     'models' => [
                '206' => '206', '207' => '207', '208' => '208', '301' => '301',
                '308' => '308', '408' => '408', '508' => '508', '2008' => '2008',
                '3008' => '3008', '5008' => '5008',
            ]],
            ['ar' => 'رينو',        'en' => 'Renault',     'models' => [
                'سيمبول' => 'Symbol', 'لوجان' => 'Logan', 'داستر' => 'Duster',
                'فلونس' => 'Fluence', 'ميجان' => 'Megane', 'لاتيتود' => 'Latitude',
                'كوليوس' => 'Koleos', 'كابتور' => 'Captur', 'كليو' => 'Clio',
            ]],
            ['ar' => 'سوزوكي',      'en' => 'Suzuki',      'models' => [
                'سويفت' => 'Swift', 'فيتارا' => 'Vitara', 'ألتو' => 'Alto',
                'بالينو' => 'Baleno', 'سياز' => 'Ciaz', 'إيرتيجا' => 'Ertiga',
                'جيمني' => 'Jimny', 'إس-كروس' => 'S-Cross',
            ]],
            ['ar' => 'ميتسوبيشي',   'en' => 'Mitsubishi',  'models' => [
                'لانسر' => 'Lancer', 'إكليبس' => 'Eclipse Cross', 'باجيرو' => 'Pajero',
                'L200' => 'L200', 'أوتلاندر' => 'Outlander', 'ASX' => 'ASX',
                'جالانت' => 'Galant', 'سبيس ستار' => 'Space Star',
            ]],
            ['ar' => 'لادا',        'en' => 'Lada',        'models' => [
                'فيستا' => 'Vesta', 'جرانتا' => 'Granta', 'نيفا' => 'Niva',
                '2107' => '2107', 'لارجوس' => 'Largus', 'XRAY' => 'XRAY',
            ]],
            ['ar' => 'فولكسفاجن',   'en' => 'Volkswagen',  'models' => [
                'جولف' => 'Golf', 'باسات' => 'Passat', 'جيتا' => 'Jetta',
                'تيجوان' => 'Tiguan', 'توارج' => 'Touareg', 'بولو' => 'Polo',
                'أطلس' => 'Atlas',
            ]],
            ['ar' => 'أودي',        'en' => 'Audi',        'models' => [
                'A3' => 'A3', 'A4' => 'A4', 'A5' => 'A5', 'A6' => 'A6',
                'A7' => 'A7', 'A8' => 'A8', 'Q3' => 'Q3', 'Q5' => 'Q5',
                'Q7' => 'Q7', 'Q8' => 'Q8', 'TT' => 'TT',
            ]],
            ['ar' => 'بورشه',       'en' => 'Porsche',     'models' => [
                'كايين' => 'Cayenne', 'ماكان' => 'Macan', '911' => '911',
                'باناميرا' => 'Panamera', 'تايكان' => 'Taycan',
            ]],
            ['ar' => 'جيب',         'en' => 'Jeep',        'models' => [
                'رانجلر' => 'Wrangler', 'جراند شيروكي' => 'Grand Cherokee',
                'كومباس' => 'Compass', 'رينيجاد' => 'Renegade', 'جلاديتور' => 'Gladiator',
            ]],
            ['ar' => 'لكزس',        'en' => 'Lexus',       'models' => [
                'IS' => 'IS', 'ES' => 'ES', 'GS' => 'GS', 'LS' => 'LS',
                'NX' => 'NX', 'RX' => 'RX', 'GX' => 'GX', 'LX' => 'LX',
                'UX' => 'UX', 'LC' => 'LC',
            ]],
            ['ar' => 'إنفينيتي',    'en' => 'Infiniti',    'models' => [
                'Q50' => 'Q50', 'Q60' => 'Q60', 'Q70' => 'Q70',
                'QX50' => 'QX50', 'QX60' => 'QX60', 'QX80' => 'QX80',
            ]],
            ['ar' => 'جينيسيس',     'en' => 'Genesis',     'models' => [
                'G70' => 'G70', 'G80' => 'G80', 'G90' => 'G90',
                'GV70' => 'GV70', 'GV80' => 'GV80',
            ]],
            ['ar' => 'شيري',        'en' => 'Chery',       'models' => [
                'تيجو 4' => 'Tiggo 4', 'تيجو 7' => 'Tiggo 7', 'تيجو 8' => 'Tiggo 8',
                'أريزو 5' => 'Arrizo 5', 'أريزو 6' => 'Arrizo 6',
            ]],
            ['ar' => 'جيلي',        'en' => 'Geely',       'models' => [
                'إيمجرانت' => 'Emgrand', 'كولراي' => 'Coolray', 'أتلاس' => 'Atlas',
                'توجيلا' => 'Tugella', 'أوكافانجو' => 'Okavango',
            ]],
            ['ar' => 'أخرى',        'en' => 'Other',       'models' => [
                'أخرى' => 'Other',
            ]],
        ];

        foreach ($data as $brandData) {
            $brand = CarBrand::create([
                'name_ar'   => $brandData['ar'],
                'name_en'   => $brandData['en'],
                'slug'      => Str::slug($brandData['en']),
                'is_active' => true,
            ]);

            foreach ($brandData['models'] as $modelAr => $modelEn) {
                CarModel::create([
                    'car_brand_id' => $brand->id,
                    'name_ar'      => $modelAr,
                    'name_en'      => $modelEn,
                    'is_active'    => true,
                ]);
            }
        }
    }
}