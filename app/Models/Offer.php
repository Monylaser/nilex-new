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
    ];

    // الإعلان المربوط بيه العرض
    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
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