<?php

namespace App\Observers;

use App\Models\Review;
use App\Models\User;

/**
 * يبقي users.ratings_avg / users.ratings_count متزامنين مع جدول reviews
 * (نمط PointTransactionObserver الذي يبقي points_balance متزامناً).
 *
 * يعاد الحساب على كل created/updated/deleted لضمان الدقة حتى لو تغيّر
 * reviewee_id أو rating. نستخدم updateQuietly لتفادي أي حلقات observer.
 */
class ReviewObserver
{
    public function created(Review $review): void
    {
        $this->recalculate($review->reviewee_id);
    }

    public function updated(Review $review): void
    {
        // لو تغيّر البائع المُقيَّم، نحدّث القديم والجديد معاً
        if ($review->wasChanged('reviewee_id')) {
            $original = $review->getOriginal('reviewee_id');
            if ($original) {
                $this->recalculate((int) $original);
            }
        }

        $this->recalculate($review->reviewee_id);
    }

    public function deleted(Review $review): void
    {
        $this->recalculate($review->reviewee_id);
    }

    protected function recalculate(?int $revieweeId): void
    {
        if (! $revieweeId) {
            return;
        }

        $user = User::find($revieweeId);

        if (! $user) {
            return;
        }

        $stats = Review::query()
            ->where('reviewee_id', $revieweeId)
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as count_rating')
            ->first();

        $count = (int) ($stats->count_rating ?? 0);

        $user->updateQuietly([
            'ratings_avg'   => $count > 0 ? round((float) $stats->avg_rating, 2) : null,
            'ratings_count' => $count,
        ]);
    }
}
