<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-time data backfill for the "account-confirmation vs real-phone-verification"
 * separation (see CLAUDE.md → "Phone vs Email Verification Separation").
 *
 * Historically registration OTP set is_phone_verified=true regardless of whether
 * the verified contact was an email or a phone. After the gate was widened to
 * accept email_verified_at, existing rows need correcting so the flags become
 * semantically accurate (and nobody is locked out).
 *
 * SAFETY: this command is **read-only by default** (a dry-run). It writes NOTHING
 * unless you pass --execute explicitly. It always prints the per-state counts
 * first, in both modes. Anonymized users (anonymized_at NOT NULL) are NEVER
 * touched.
 *
 * Backfill rules:
 *   [A] is_phone_verified=true & phone IS NULL  (mis-flagged email/social):
 *         -> is_phone_verified=false + email_verified_at=now()
 *         -> ONLY where email_verified_at IS currently NULL (never overwrite an
 *            existing timestamp; rows that already have one are left untouched).
 *   [B] is_phone_verified=true & phone IS NOT NULL  (a real verified phone):
 *         -> phone_verified_at=now()
 *         -> ONLY where phone_verified_at IS currently NULL.
 */
class BackfillUserVerification extends Command
{
    protected $signature = 'users:backfill-verification {--execute : Actually perform the UPDATEs. Omit for a safe, read-only dry-run.}';

    protected $description = 'Backfill user verification flags (account-confirmation vs real-phone-verification). Dry-run by default; writes only with --execute.';

    public function handle(): int
    {
        $execute = (bool) $this->option('execute');

        // ── Active (non-anonymized) base; anonymized users are never touched ──
        $active = fn () => User::query()->whereNull('anonymized_at');

        $anonymized = User::query()->whereNotNull('anonymized_at')->count();

        // ── [A] mis-flagged: verified flag true but no real phone ──
        $aTotal = $active()->where('is_phone_verified', true)->whereNull('phone')->count();
        $aActionable = $active()->where('is_phone_verified', true)->whereNull('phone')
            ->whereNull('email_verified_at')->count();
        $aSkippedHasEmailVerified = $aTotal - $aActionable;

        // ── [B] real verified phone: needs phone_verified_at timestamp ──
        $bTotal = $active()->where('is_phone_verified', true)->whereNotNull('phone')->count();
        $bActionable = $active()->where('is_phone_verified', true)->whereNotNull('phone')
            ->whereNull('phone_verified_at')->count();
        $bSkippedHasTimestamp = $bTotal - $bActionable;

        // ── Report (printed in BOTH modes, before any write) ──
        $this->line('');
        $this->line('================ users:backfill-verification ================');
        $this->line('Mode: '.($execute ? 'EXECUTE (will write)' : 'DRY-RUN (read-only, no writes)'));
        $this->line('-------------------------------------------------------------');
        $this->line('total_active                                  = '.$active()->count());
        $this->line('anonymized (never touched)                    = '.$anonymized);
        $this->line('');
        $this->line('[A] is_phone_verified=true & phone IS NULL    = '.$aTotal);
        $this->line('      -> WILL UPDATE (email_verified_at NULL)  = '.$aActionable.'   [set is_phone_verified=false + email_verified_at=now()]');
        $this->line('      -> skipped (email_verified_at already set)= '.$aSkippedHasEmailVerified);
        $this->line('');
        $this->line('[B] is_phone_verified=true & phone NOT NULL   = '.$bTotal);
        $this->line('      -> WILL UPDATE (phone_verified_at NULL)  = '.$bActionable.'   [set phone_verified_at=now()]');
        $this->line('      -> skipped (phone_verified_at already set)= '.$bSkippedHasTimestamp);
        $this->line('=============================================================');

        if (! $execute) {
            $this->warn('[dry-run] No changes were written. Re-run with --execute to apply the updates above.');

            return self::SUCCESS;
        }

        if ($aActionable === 0 && $bActionable === 0) {
            $this->info('Nothing to update — all rows are already consistent.');

            return self::SUCCESS;
        }

        // ── Actual writes (only with --execute), atomic ──
        [$aUpdated, $bUpdated] = DB::transaction(function () {
            $a = User::query()
                ->whereNull('anonymized_at')
                ->where('is_phone_verified', true)
                ->whereNull('phone')
                ->whereNull('email_verified_at')
                ->update(['is_phone_verified' => false, 'email_verified_at' => now()]);

            $b = User::query()
                ->whereNull('anonymized_at')
                ->where('is_phone_verified', true)
                ->whereNotNull('phone')
                ->whereNull('phone_verified_at')
                ->update(['phone_verified_at' => now()]);

            return [$a, $b];
        });

        $this->info("Done. [A] updated {$aUpdated} row(s); [B] updated {$bUpdated} row(s).");

        return self::SUCCESS;
    }
}
