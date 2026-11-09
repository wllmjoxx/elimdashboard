<?php

namespace App\Modules\Devotionals\Policies;

use App\Models\User;
use App\Modules\Devotionals\Enums\DevotionalStatus;
use App\Modules\Devotionals\Models\Devotional;

/**
 * PRD 4.2: Pelayan writes drafts; Admin reviews, publishes and edits any
 * devotional of the branch. Branch scoping comes from BelongsToChurch, so a
 * devotional of another branch is not found at all. Superadmin passes every
 * check through Gate::before (Core).
 */
class DevotionalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('devotionals.view');
    }

    public function create(User $user): bool
    {
        return $user->can('devotionals.write');
    }

    public function update(User $user, Devotional $devotional): bool
    {
        return $user->can('devotionals.publish') || $this->ownDraft($user, $devotional);
    }

    public function publish(User $user, Devotional $devotional): bool
    {
        return $user->can('devotionals.publish');
    }

    public function delete(User $user, Devotional $devotional): bool
    {
        return $user->can('devotionals.publish') || $this->ownDraft($user, $devotional);
    }

    private function ownDraft(User $user, Devotional $devotional): bool
    {
        return $user->can('devotionals.write')
            && $devotional->status === DevotionalStatus::Draft
            && $devotional->created_by === $user->id;
    }
}
