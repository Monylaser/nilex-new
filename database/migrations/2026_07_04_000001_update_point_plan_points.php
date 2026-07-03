<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('point_plans')) {
            return;
        }

        $byTier = [
            'starter'    => 100,
            'growth'     => 300,
            'pro_seller' => 850,
            'business'   => 2500,
        ];

        foreach ($byTier as $tierKey => $points) {
            DB::table('point_plans')
                ->where('tier_key', $tierKey)
                ->update(['points' => $points]);
        }

        $byNameEn = [
            'Starter'    => 100,
            'Growth'     => 300,
            'Pro Seller' => 850,
            'Business'   => 2500,
        ];

        foreach ($byNameEn as $nameEn => $points) {
            DB::table('point_plans')
                ->where('name_en', $nameEn)
                ->update(['points' => $points]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('point_plans')) {
            return;
        }

        $byTier = [
            'starter'    => 50,
            'growth'     => 250,
            'pro_seller' => 700,
            'business'   => 2000,
        ];

        foreach ($byTier as $tierKey => $points) {
            DB::table('point_plans')
                ->where('tier_key', $tierKey)
                ->update(['points' => $points]);
        }

        $byNameEn = [
            'Starter'    => 50,
            'Growth'     => 250,
            'Pro Seller' => 700,
            'Business'   => 2000,
        ];

        foreach ($byNameEn as $nameEn => $points) {
            DB::table('point_plans')
                ->where('name_en', $nameEn)
                ->update(['points' => $points]);
        }
    }
};
