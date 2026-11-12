<?php

namespace App\Modules\Announcements\Policies;

use App\Models\User;
use App\Modules\Announcements\Models\Announcement;

/**
 * Admin manages the announcements of their branch; branch scoping comes from
 * BelongsToChurch. Superadmin passes every check through Gate::before.
 */
class AnnouncementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('announcements.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('announcements.manage');
    }

    public function update(User $user, Announcement $announcement): bool
    {
        return $user->can('announcements.manage');
    }

    public function delete(User $user, Announcement $announcement): bool
    {
        return $user->can('announcements.manage');
    }
}
