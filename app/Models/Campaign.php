<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    protected $fillable = [
        'title',
        'message',
        'target_group',
        'status',
        'scheduled_at',
        'sent_at',
        'recipients_count',
    ];

    protected $casts = [
        'scheduled_at'     => 'datetime',
        'sent_at'          => 'datetime',
        'recipients_count' => 'integer',
    ];

    // ── Status constants ──────────────────────────────────────────────────────

    const STATUS_DRAFT     = 'draft';
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_SENT      = 'sent';
    const STATUS_FAILED    = 'failed';

    // ── Target-group constants ────────────────────────────────────────────────

    const TARGET_ALL         = 'all_users';
    const TARGET_VERIFIED    = 'verified_users';
    const TARGET_SELLERS     = 'active_sellers';
    const TARGET_NEW         = 'new_users';
    const TARGET_BANNED      = 'banned_users';

    public static function statusOptions(): array
    {
        return [
            self::STATUS_DRAFT     => 'مسودة',
            self::STATUS_SCHEDULED => 'مجدولة',
            self::STATUS_SENT      => 'مُرسلة',
            self::STATUS_FAILED    => 'فشل',
        ];
    }

    public static function targetGroupOptions(): array
    {
        return [
            self::TARGET_ALL      => 'جميع المستخدمين',
            self::TARGET_VERIFIED => 'المستخدمون الموثقون',
            self::TARGET_SELLERS  => 'البائعون النشطون',
            self::TARGET_NEW      => 'المستخدمون الجدد (آخر 30 يوم)',
            self::TARGET_BANNED   => 'المحظورون',
        ];
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeDue($query)
    {
        return $query
            ->where('status', self::STATUS_SCHEDULED)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->whereNull('sent_at');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function resolveRecipients(): \Illuminate\Database\Eloquent\Builder
    {
        $query = User::query();

        return match ($this->target_group) {
            self::TARGET_VERIFIED => $query->whereNotNull('email_verified_at'),
            self::TARGET_SELLERS  => $query->whereHas('listings', fn ($q) => $q->where('status', 'published')),
            self::TARGET_NEW      => $query->where('created_at', '>=', now()->subDays(30)),
            self::TARGET_BANNED   => $query->where('is_banned', true),
            default               => $query,
        };
    }
}
