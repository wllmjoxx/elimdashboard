<?php

namespace App\Modules\Testimonies\Policies;

use App\Models\User;
use App\Modules\Core\Support\CurrentChurch;
use App\Modules\Testimonies\Models\Testimony;

/**
 * Moderation is for Admin and Superadmin of the branch (PRD 4.2).
 */
class TestimonyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('testimonies.moderate');
    }

    public function moderate(User $user, Testimony $testimony): bool
    {
        $current = app(CurrentChurch::class);

        return $user->can('testimonies.moderate')
            && (! $current->restricts() || $testimony->church_id === $current->id());
    }
}
