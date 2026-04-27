<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PhoneBrand;
use App\Models\PhoneModel;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

class PhoneBrandSeeder extends Seeder
{
    public function run(): void
    {
        // 1. تعطيل فحص العلاقات الخارجية لتجنب الأخطاء أثناء المسح
        Schema::disableForeignKeyConstraints();

        // 2. مسح البيانات القديمة بالترتيب (الموديلات أولاً ثم الماركات)
        PhoneModel::truncate();
        PhoneBrand::truncate();

        // 3. إعادة تفعيل الفحص
        Schema::enableForeignKeyConstraints();

        $data = [
            ['ar' => 'سامسونج',  'en' => 'Samsung',  'models' => [
                'Galaxy S24 Ultra', 'Galaxy S24+', 'Galaxy S24', 'Galaxy S23 Ultra',
                'Galaxy S23+', 'Galaxy S23', 'Galaxy S22 Ultra', 'Galaxy S22',
                'Galaxy A55', 'Galaxy A54', 'Galaxy A35', 'Galaxy A34',
                'Galaxy A15', 'Galaxy A14', 'Galaxy A05', 'Galaxy A04',
                'Galaxy Z Fold 5', 'Galaxy Z Fold 4', 'Galaxy Z Flip 5', 'Galaxy Z Flip 4',
                'Galaxy M34', 'Galaxy M14', 'Galaxy F54', 'Galaxy Note 20 Ultra',
            ]],
            ['ar' => 'آبل',      'en' => 'Apple',    'models' => [
                'iPhone 16 Pro Max', 'iPhone 16 Pro', 'iPhone 16 Plus', 'iPhone 16',
                'iPhone 15 Pro Max', 'iPhone 15 Pro', 'iPhone 15 Plus', 'iPhone 15',
                'iPhone 14 Pro Max', 'iPhone 14 Pro', 'iPhone 14 Plus', 'iPhone 14',
                'iPhone 13 Pro Max', 'iPhone 13 Pro', 'iPhone 13', 'iPhone 13 Mini',
                'iPhone 12 Pro Max', 'iPhone 12 Pro', 'iPhone 12', 'iPhone 12 Mini',
                'iPhone 11 Pro Max', 'iPhone 11 Pro', 'iPhone 11',
                'iPhone SE 3rd Gen', 'iPhone SE 2nd Gen', 'iPhone XS Max',
            ]],
            ['ar' => 'شاومي',    'en' => 'Xiaomi',   'models' => [
                'Xiaomi 14 Ultra', 'Xiaomi 14 Pro', 'Xiaomi 14',
                'Xiaomi 13 Ultra', 'Xiaomi 13 Pro', 'Xiaomi 13',
                'Redmi Note 13 Pro+', 'Redmi Note 13 Pro', 'Redmi Note 13',
                'Redmi Note 12 Pro+', 'Redmi Note 12 Pro', 'Redmi Note 12',
                'Redmi 13C', 'Redmi 12C', 'Redmi 12', 'Redmi A3',
                'POCO X6 Pro', 'POCO X6', 'POCO F5 Pro', 'POCO F5',
                'POCO M6 Pro', 'POCO M5', 'POCO C65',
            ]],
            ['ar' => 'أوبو',     'en' => 'OPPO',     'models' => [
                'Find X7 Ultra', 'Find X7 Pro', 'Find X7',
                'Find X6 Pro', 'Find X6', 'Find N3 Flip',
                'Reno 11 Pro', 'Reno 11', 'Reno 10 Pro+', 'Reno 10 Pro', 'Reno 10',
                'A98', 'A78', 'A58', 'A38', 'A18', 'A17',
                'F25 Pro', 'F23', 'K12',
            ]],
            ['ar' => 'فيفو',     'en' => 'Vivo',     'models' => [
                'X100 Ultra', 'X100 Pro', 'X100',
                'X90 Pro+', 'X90 Pro', 'X90',
                'V30 Pro', 'V30', 'V29 Pro', 'V29', 'V27 Pro', 'V27',
                'Y200', 'Y100', 'Y78', 'Y56', 'Y36', 'Y28', 'Y17s',
                'T3x', 'T3 Pro', 'T3',
            ]],
            ['ar' => 'هواوي',    'en' => 'Huawei',   'models' => [
                'Mate 60 Pro+', 'Mate 60 Pro', 'Mate 60', 'Mate 50 Pro', 'Mate 50',
                'P60 Pro', 'P60', 'P50 Pro', 'P50', 'P40 Pro', 'P40',
                'Nova 12 Pro', 'Nova 12', 'Nova 11 Pro', 'Nova 11',
                'Nova 10 Pro', 'Nova 10', 'Nova Y90', 'Nova Y70',
                'Y9s', 'Y7a', 'Enjoy 60',
            ]],
            ['ar' => 'ريلمي',    'en' => 'Realme',   'models' => [
                'GT 5 Pro', 'GT 5', 'GT Neo 6', 'GT Neo 5',
                '12 Pro+', '12 Pro', '12', '12+',
                '11 Pro+', '11 Pro', '11', 'Narzo 70 Pro', 'Narzo 70',
                'C67', 'C55', 'C53', 'C51', 'C35', 'C33',
            ]],
            ['ar' => 'وان بلس',  'en' => 'OnePlus',  'models' => [
                'OnePlus 12', 'OnePlus 12R', 'OnePlus 11', 'OnePlus 11R',
                'OnePlus Nord 4', 'OnePlus Nord 3', 'OnePlus Nord CE 4',
                'OnePlus Nord CE 3 Lite', 'OnePlus Nord N30', 'OnePlus Open',
            ]],
            ['ar' => 'موتورولا', 'en' => 'Motorola', 'models' => [
                'Edge 50 Ultra', 'Edge 50 Pro', 'Edge 50', 'Edge 50 Fusion',
                'Edge 40 Pro', 'Edge 40', 'Edge 30 Ultra',
                'Moto G84', 'Moto G54', 'Moto G34', 'Moto G14',
                'Razr 50 Ultra', 'Razr 50', 'Razr 40 Ultra', 'Razr 40',
            ]],
            ['ar' => 'نوكيا',    'en' => 'Nokia',    'models' => [
                'Nokia G42', 'Nokia G22', 'Nokia G21', 'Nokia G11',
                'Nokia C32', 'Nokia C22', 'Nokia C12', 'Nokia C02',
                'Nokia XR21', 'Nokia X30', 'Nokia X10',
                'Nokia 8210 4G', 'Nokia 3310 4G',
            ]],
            ['ar' => 'إنفينكس', 'en' => 'Infinix',  'models' => [
                'Zero 40', 'Zero 30', 'Zero 20',
                'Note 40 Pro+', 'Note 40 Pro', 'Note 40', 'Note 30 Pro', 'Note 30',
                'Hot 40 Pro', 'Hot 40', 'Hot 30i', 'Hot 20i',
                'Smart 8', 'Smart 7', 'GT 10 Pro',
            ]],
            ['ar' => 'تكنو',     'en' => 'Tecno',    'models' => [
                'Camon 30 Pro', 'Camon 30', 'Camon 20 Pro', 'Camon 20',
                'Spark 20 Pro+', 'Spark 20 Pro', 'Spark 20', 'Spark 10 Pro',
                'Pop 8', 'Pop 7 Pro', 'Pova 6 Pro', 'Pova 6',
                'Phantom X2 Pro', 'Phantom X2',
            ]],
            ['ar' => 'آيتل',     'en' => 'Itel',     'models' => [
                'A70', 'A60s', 'A60', 'A50', 'P40', 'P38',
                'S24', 'S23', 'Vision 3 Plus', 'Vision 2S',
            ]],
            ['ar' => 'سوني',     'en' => 'Sony',     'models' => [
                'Xperia 1 VI', 'Xperia 1 V', 'Xperia 5 V', 'Xperia 5 IV',
                'Xperia 10 VI', 'Xperia 10 V', 'Xperia Pro-I',
            ]],
            ['ar' => 'أخرى',     'en' => 'Other',    'models' => ['أخرى']],
        ];

        foreach ($data as $sort => $brandData) {
            $brand = PhoneBrand::create([
                'name_ar'    => $brandData['ar'],
                'name_en'    => $brandData['en'],
                'slug'       => Str::slug($brandData['en']),
                'is_active'  => true,
                'sort_order' => $sort + 1,
            ]);

            foreach ($brandData['models'] as $modelName) {
                PhoneModel::create([
                    'phone_brand_id' => $brand->id,
                    'name_ar'        => $modelName,
                    'name_en'        => $modelName,
                    'slug'           => Str::slug($modelName),
                    'is_active'      => true,
                    'sort_order'     => 0,
                ]);
            }
        }
    }
}