<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AdCampaign;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class AdCampaignPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AdCampaign');
    }

    public function view(AuthUser $authUser, AdCampaign $campaign): bool
    {
        return $authUser->can('View:AdCampaign')
            || $this->ownsCampaign($authUser, $campaign);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AdCampaign');
    }

    public function update(AuthUser $authUser, AdCampaign $campaign): bool
    {
        return $authUser->can('Update:AdCampaign');
    }

    public function delete(AuthUser $authUser, AdCampaign $campaign): bool
    {
        return $authUser->can('Delete:AdCampaign');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:AdCampaign');
    }

    public function restore(AuthUser $authUser, AdCampaign $campaign): bool
    {
        return $authUser->can('Restore:AdCampaign');
    }

    public function forceDelete(AuthUser $authUser, AdCampaign $campaign): bool
    {
        return $authUser->can('ForceDelete:AdCampaign');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AdCampaign');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AdCampaign');
    }

    public function replicate(AuthUser $authUser, AdCampaign $campaign): bool
    {
        return $authUser->can('Replicate:AdCampaign');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AdCampaign');
    }

    public function approve(AuthUser $authUser, AdCampaign $campaign): bool
    {
        return $authUser->can('Update:AdCampaign');
    }

    public function reject(AuthUser $authUser, AdCampaign $campaign): bool
    {
        return $authUser->can('Update:AdCampaign');
    }

    public function retryPayment(AuthUser $authUser, AdCampaign $campaign): bool
    {
        return $this->ownsCampaign($authUser, $campaign)
            && in_array($campaign->payment_status, ['failed', 'pending'], true);
    }

    private function ownsCampaign(AuthUser $authUser, AdCampaign $campaign): bool
    {
        return $campaign->seller_id !== null
            && (int) $campaign->seller_id === (int) $authUser->id;
    }
}
