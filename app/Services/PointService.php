<?php

// app/Services/PointService.php

namespace App\Services;

use App\Exceptions\InsufficientPointsException;
use App\Models\PointTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PointService
{
    // ── Public API ───────────────────────────────────────────────

    /**
     * Credit points to a user.
     *
     * @param  Model|null  $reference  Any Eloquent model to link (Listing, Order…)
     */
    public function credit(
        User $user,
        int $amount,
        string $description,
        ?Model $reference = null,
    ): PointTransaction {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Credit amount must be positive.');
        }

        return $this->record($user, $amount, $description, $reference);
    }

    /**
     * Deduct points from a user.
     *
     * @throws InsufficientPointsException
     */
    public function deduct(
        User $user,
        int $amount,
        string $description,
        ?Model $reference = null,
    ): PointTransaction {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Deduct amount must be positive.');
        }

        // Re-read the balance inside the transaction (see record()), but do a
        // quick pre-flight check here for a friendlier early error.
        if ($user->points < $amount) {
            throw new InsufficientPointsException($amount, $user->points);
        }

        return $this->record($user, -$amount, $description, $reference);
    }

    /**
     * Transfer points between two users atomically.
     *
     * @throws InsufficientPointsException
     */
    public function transfer(
        User $from,
        User $to,
        int $amount,
        string $description,
        ?Model $reference = null,
    ): array {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Transfer amount must be positive.');
        }

        return DB::transaction(function () use ($from, $to, $amount, $description, $reference) {
            // Lock both user rows in a stable order to prevent deadlocks under
            // concurrent opposite-direction transfers.
            $lockIds = [$from->id, $to->id];
            sort($lockIds);

            User::query()
                ->whereIn('id', $lockIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            /** @var User $lockedFrom */
            $lockedFrom = User::query()->lockForUpdate()->findOrFail($from->id);
            /** @var User $lockedTo */
            $lockedTo = User::query()->lockForUpdate()->findOrFail($to->id);

            if ($lockedFrom->points < $amount) {
                throw new InsufficientPointsException($amount, $lockedFrom->points);
            }

            $debit = $this->applyBalanceChangeOnLockedUser(
                $lockedFrom,
                -$amount,
                "Transfer out: {$description}",
                $reference,
                $from,
            );

            $credit = $this->applyBalanceChangeOnLockedUser(
                $lockedTo,
                $amount,
                "Transfer in: {$description}",
                $reference,
                $to,
            );

            return ['debit' => $debit, 'credit' => $credit];
        });
    }

    /**
     * Return the current verified balance directly from the DB.
     * Always use this instead of $user->points for guaranteed accuracy.
     * Both `points` and `points_balance` are kept in sync — `points` is
     * the single source of truth; `points_balance` mirrors it so the
     * admin panel can display it without extra queries.
     */
    public function getBalance(User $user): int
    {
        return (int) User::where('id', $user->id)->value('points');
    }

    // ── Core private logic ───────────────────────────────────────

    /**
     * Persist both the transaction log and the user's balance atomically.
     *
     * We use SELECT … FOR UPDATE to lock the user row for the duration of the
     * DB transaction, preventing race conditions under concurrent requests.
     */
    private function record(
        User $user,
        int $amount,       // already signed (+/-)
        string $description,
        ?Model $reference,
    ): PointTransaction {
        return DB::transaction(function () use ($user, $amount, $description, $reference) {

            // 1. Lock the user row to prevent concurrent balance corruption.
            /** @var User $lockedUser */
            $lockedUser = User::lockForUpdate()->findOrFail($user->id);

            // 2. For debits, re-validate balance against the *locked* record.
            if ($amount < 0 && $lockedUser->points < abs($amount)) {
                throw new InsufficientPointsException(abs($amount), $lockedUser->points);
            }

            return $this->applyBalanceChangeOnLockedUser(
                $lockedUser,
                $amount,
                $description,
                $reference,
                $user,
            );
        });
    }

    /**
     * Apply a signed balance change on a row already locked via SELECT … FOR UPDATE.
     * Caller must hold the lock inside an open DB transaction.
     */
    private function applyBalanceChangeOnLockedUser(
        User $lockedUser,
        int $amount,
        string $description,
        ?Model $reference,
        ?User $callerModel = null,
    ): PointTransaction {
        // Keep both columns in sync — `points` is source of truth; `points_balance` mirrors it.
        $lockedUser->increment('points', $amount);
        $lockedUser->update(['points_balance' => $lockedUser->points]);

        $transaction = new PointTransaction([
            'user_id' => $lockedUser->id,
            'amount' => $amount,
            'current_balance' => $lockedUser->points,
            'description' => $description,
        ]);

        if ($reference) {
            $transaction->reference()->associate($reference);
        }

        $transaction->save();

        if ($callerModel !== null) {
            $callerModel->points = $lockedUser->points;
            $callerModel->points_balance = $lockedUser->points;
        }

        return $transaction;
    }
}
