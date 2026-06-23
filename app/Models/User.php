<?php

namespace App\Models;

use App\Models\Concerns\HasPlanType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, LogsActivity, HasPlanType;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'phone', 'is_banned', 'ban_reason', 'strike_count'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $event) => "User {$event}");
    }

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'points',
        'points_balance',
        'is_banned',
        'ban_reason',
        'strike_count',
        'avatar',
        'ip_address', 
        'device_id',
        'fingerprint_hash',
        'otp_code',
        'otp_expires_at',
        'otp_attempts',
        'is_phone_verified', 
        'provider_name',
        'provider_id',
        'plan_tier',
        'plan_type',
        'locale',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'otp_code',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'is_banned'         => 'boolean',
        'strike_count'      => 'integer',
        'otp_expires_at'    => 'datetime',
        'is_phone_verified' => 'boolean',
        'otp_attempts'      => 'integer',
    ];

    // ── Panel Access ──────────────────────────────────────────────────────────

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasRole('super_admin')
            || $this->hasRole('admin')
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

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class)->latest();
    }

    public function listingViews(): HasMany
    {
        return $this->hasMany(ListingView::class);
    }

    public function listingPhoneClicks(): HasMany
    {
        return $this->hasMany(ListingPhoneClick::class);
    }

    public function listingWhatsappClicks(): HasMany
    {
        return $this->hasMany(ListingWhatsappClick::class);
    }

    public function entitlements(): HasMany
    {
        return $this->hasMany(UserEntitlement::class);
    }

    public function entitlementUsage(): HasMany
    {
        return $this->hasMany(UserEntitlementUsage::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * `points` هو الرصيد الفعلي الوحيد — `points_balance` مجرد مرآة له عبر PointService.
     */
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