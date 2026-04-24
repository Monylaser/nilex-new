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
        User   $user,
        int    $amount,
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
        User   $user,
        int    $amount,
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
        User   $from,
        User   $to,
        int    $amount,
        string $description,
        ?Model $reference = null,
    ): array {
        return DB::transaction(function () use ($from, $to, $amount, $description, $reference) {
            $debit  = $this->deduct($from, $amount, "Transfer out: {$description}", $reference);
            $credit = $this->credit($to,   $amount, "Transfer in: {$description}",  $reference);

            return ['debit' => $debit, 'credit' => $credit];
        });
    }

    /**
     * Return the current verified balance directly from the DB.
     * Use this instead of $user->points when you need guaranteed accuracy.
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
        User   $user,
        int    $amount,       // already signed (+/-)
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

            // 3. Update balance atomically using a DB expression (not PHP arithmetic).
            $lockedUser->increment('points', $amount);
            $newBalance = $lockedUser->points + $amount; // increment() updates the model too

            // 4. Log the transaction.
            $transaction = new PointTransaction([
                'user_id'         => $lockedUser->id,
                'amount'          => $amount,
                'current_balance' => $lockedUser->points, // already updated by increment()
                'description'     => $description,
            ]);

            if ($reference) {
                $transaction->reference()->associate($reference);
            }

            $transaction->save();

            // 5. Sync the in-memory model so callers see the new balance.
            $user->points = $lockedUser->points;

            return $transaction;
        });
    }
}
