<?php

namespace App\Modules\Members\Policies;

use App\Models\User;
use App\Modules\Core\Support\CurrentChurch;
use App\Modules\Members\Models\Member;
use App\Modules\Members\Support\RoleAssignment;

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

    /**
     * Change an account's role (PRD 4.2). Nobody changes their own role, and
     * Admin cannot touch Admin or Superadmin accounts.
     */
    public function assignRole(User $user, Member $member): bool
    {
        return $user->can('members.roles')
            && $this->sameChurch($member)
            && $user->isNot($member)
            && ! app(RoleAssignment::class)->protects($user, $member);
    }

    /**
     * Invite a new staff account by email (PRD 4.4).
     */
    public function invite(User $user): bool
    {
        return $user->can('members.roles');
    }

    /**
     * Admin cannot deactivate Admin or Superadmin accounts (PRD 4.2: those
     * are managed by Superadmin).
     */
    public function deactivate(User $user, Member $member): bool
    {
        return $user->can('members.deactivate')
            && $this->sameChurch($member)
            && $user->isNot($member)
            && ! app(RoleAssignment::class)->protects($user, $member);
    }

    private function sameChurch(Member $member): bool
    {
        $current = app(CurrentChurch::class);

        return ! $current->restricts() || $member->church_id === $current->id();
    }
}
