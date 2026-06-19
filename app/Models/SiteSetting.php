<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = [
        'site_name', 'site_email', 'site_phone',
        'site_logo', 'site_favicon', 'is_active',
        'auth_bg_image', 'auth_bg_type', 'auth_bg_color',
        'auth_headline', 'auth_subtext',
    ];

    public static function getSettings(): self
    {
        return static::query()->orderBy('id')->first()
            ?? static::create([
                'auth_bg_type' => 'color',
                'auth_bg_color' => '#085041',
                'is_active' => true,
            ]);
    }
}