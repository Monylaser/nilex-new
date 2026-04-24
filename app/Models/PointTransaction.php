<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointTransaction extends Model
{
    // السماح بتعبئة هذه الحقول برمجياً
    protected $fillable = [
        'user_id',
        'amount',
        'current_balance',
        'description',
        'reference_id',
    ];

    /**
     * علاقة العملية بالمستخدم (كل عملية تنتمي لمستخدم واحد)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    // جوه كلاس PointTransaction في ملف app/Models/PointTransaction.php

public function reference()
{
    return $this->morphTo();
}
}
