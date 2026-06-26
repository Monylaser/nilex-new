<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Offer extends Model
{
    use HasFactory;

    protected $fillable = [
        'listing_id',
        'sender_id',
        'receiver_id',
        'amount',
        'message',
        'status',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'responded_at' => 'datetime',
        ];
    }

    // الإعلان المربوط بيه العرض — withTrashed لأن الإعلان يُغلق (soft delete)
    // عبر sold_platform/sold_external بينما تبقى العروض القديمة معلّقة وتعرض
    // listing في لوحة التحكم؛ بدونها ترجع null وتكسر الصفحة (نفس نمط
    // SaleConfirmation::listing() و Review::listing()).
    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class)->withTrashed();
    }

    // المشتري (اللي باعت العرض)
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    // البائع (صاحب الإعلان)
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }
}