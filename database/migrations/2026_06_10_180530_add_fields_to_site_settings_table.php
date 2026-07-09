<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('site_name')->nullable();
            $table->string('site_email')->nullable();
            $table->string('site_phone')->nullable();
            $table->string('site_logo')->nullable();
            $table->string('site_favicon')->nullable();
            $table->boolean('is_active')->default(true);
            // حقول الإعلانات على شاشة اللوجن
            $table->string('auth_bg_type')->default('color'); // color | image
            $table->string('auth_bg_color')->nullable()->default('#0D7377');
            $table->text('auth_headline')->nullable();
            $table->text('auth_subtext')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'site_name', 'site_email', 'site_phone',
                'site_logo', 'site_favicon', 'is_active',
                'auth_bg_type', 'auth_bg_color',
                'auth_headline', 'auth_subtext',
            ]);
        });
    }
};