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

    /**
     * Members see their own profile; Admin sees profiles in their branch.
     */
    public function view(User $user, Member $member): bool
    {
        return $user->is($member) || ($user->can('members.view') && $this->sameChurch($member));
    }

    /**
     * Membership data (status, join date, baptism) is kept by Admin.
     */
    public function updateMembership(User $user, Member $member): bool
    {
        return $user->can('members.edit') && $this->sameChurch($member);
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
