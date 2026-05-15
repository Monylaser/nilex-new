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
use Illuminate\Contracts\Auth\MustVerifyEmail;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'points',
        'is_banned',
        'ban_reason',
        'strike_count',
        'avatar',
        'ip_address', 
        'device_id',  
        'otp_code',          
        'otp_expires_at',   
        'is_phone_verified', 
        'provider_name',
        'provider_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'is_banned'         => 'boolean',
        'strike_count'      => 'integer',
    ];

    // ── Panel Access ──────────────────────────────────────────────────────────

    public function canAccessPanel(Panel $panel): bool
    {
        return str_ends_with($this->email, '@nilex.com')
            || $this->hasRole('super_admin')
            || $this->hasRole('moderator');
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'admin_id');
    }
    public function pointTransactions(): HasMany
    {
        return $this->hasMany(PointTransaction::class)->latest();
    }
    // ── Helpers ───────────────────────────────────────────────────────────────

    public function hasPoints(int $amount): bool
    {
        return $this->points >= $amount;
    }

    public function ban(string $reason = ''): void
    {
        $this->update(['is_banned' => true, 'ban_reason' => $reason]);
    }

    public function unban(): void
    {
        $this->update(['is_banned' => false, 'ban_reason' => null]);
    }

    public function addStrike(): void
    {
        $this->increment('strike_count');

        // تلقائياً حظر بعد 3 strikes
        if ($this->strike_count >= 3) {
            $this->ban('تجاوز الحد المسموح به من المخالفات');
        }
    }
}