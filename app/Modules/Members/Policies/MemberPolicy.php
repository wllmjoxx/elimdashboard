<?php

namespace App\Modules\Members\Policies;

use App\Models\User;
use App\Modules\Core\Support\CurrentChurch;
use App\Modules\Members\Models\Member;

/**
 * Admin works only inside their branch; Superadmin passes every check
 * through Gate::before (Core).
 */
class MemberPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('members.view');
    }

    public function review(User $user, Member $member): bool
    {
        return $user->can('members.approve') && $this->sameChurch($member);
    }

    public function deactivate(User $user, Member $member): bool
    {
        return $user->can('members.deactivate') && $this->sameChurch($member) && $user->isNot($member);
    }

    private function sameChurch(Member $member): bool
    {
        $current = app(CurrentChurch::class);

        return ! $current->restricts() || $member->church_id === $current->id();
    }
}
