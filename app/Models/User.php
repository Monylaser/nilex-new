<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'points',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * تحديد من يمكنه دخول لوحة التحكم
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // هيسمح بالدخول لو الإيميل هو الأدمن أو لو اليوزر عنده دور super_admin
        return str_ends_with($this->email, '@nilex.com') || $this->hasRole('super_admin');
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    public function hasPoints(int $amount): bool
    {
        return $this->points >= $amount;
    }
}