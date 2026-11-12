<?php

namespace App\Modules\Events\Policies;

use App\Models\User;
use App\Modules\Events\Models\Event;

/**
 * Admin manages the events of their branch; branch scoping comes from
 * BelongsToChurch. Superadmin passes every check through Gate::before.
 */
class EventPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('events.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('events.manage');
    }

    public function update(User $user, Event $event): bool
    {
        return $user->can('events.manage');
    }

    public function delete(User $user, Event $event): bool
    {
        return $user->can('events.manage');
    }
}
