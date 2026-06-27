<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Listing;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Listing authorization.
 *
 * IMPORTANT (security fix): this policy must check the permission names that
 * actually exist in the database. The seeded permissions are the legacy
 * snake_case Shield names — `view_listings`, `approve_listings`,
 * `reject_listings` — NOT the newer `View:Listing` / `Update:Listing` style.
 *
 * The previous version checked `View:Listing` / `Update:Listing` / etc., which
 * **no role actually has**. Only `super_admin` worked, because FilamentShield's
 * `Gate::before` bypasses every policy for super_admin. For a real `moderator`
 * (the role that staffs the pending-review queue) `view()`/`viewAny()` returned
 * false → the listing row was not clickable and the View page returned 403 →
 * moderators could not actually open and review listing content (images +
 * description) before approving/rejecting. That is a real moderation blind spot.
 *
 * Management abilities (create/update/delete/restore/forceDelete/replicate/
 * reorder) intentionally reference snake_case permission names that are not
 * seeded yet; `can()` returns false for any non-super_admin (super_admin still
 * passes via the Gate::before bypass). This keeps moderators view+approve+reject
 * only, and is forward-compatible: seed + assign the matching permission later
 * to grant the ability — no policy change needed.
 */
class ListingPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_listings');
    }

    public function view(AuthUser $authUser, Listing $listing): bool
    {
        return $authUser->can('view_listings');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_listings');
    }

    public function update(AuthUser $authUser, Listing $listing): bool
    {
        return $authUser->can('update_listings');
    }

    public function delete(AuthUser $authUser, Listing $listing): bool
    {
        return $authUser->can('delete_listings');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_listings');
    }

    public function restore(AuthUser $authUser, Listing $listing): bool
    {
        return $authUser->can('restore_listings');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_listings');
    }

    public function forceDelete(AuthUser $authUser, Listing $listing): bool
    {
        return $authUser->can('force_delete_listings');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_listings');
    }

    public function replicate(AuthUser $authUser, Listing $listing): bool
    {
        return $authUser->can('replicate_listings');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_listings');
    }
}
