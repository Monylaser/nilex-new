<?php

namespace App\Listeners\Auth;

use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Events\RoleDetachedEvent;

class LogRoleRevoked
{
    public function handle(RoleDetachedEvent $event): void
    {
        $rolesOrIds = $event->rolesOrIds;

        $roleNames = collect(is_array($rolesOrIds) ? $rolesOrIds : [$rolesOrIds])
            ->map(fn ($item) => is_object($item) ? $item->name : (string) $item)
            ->implode(', ');

        activity('roles')
            ->causedBy(Auth::user())
            ->performedOn($event->model)
            ->withProperties([
                'roles'   => $roleNames,
                'user_id' => $event->model->getKey(),
            ])
            ->log("تم سحب صلاحية: {$roleNames}");
    }
}
