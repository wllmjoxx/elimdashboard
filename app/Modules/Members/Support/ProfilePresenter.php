<?php

namespace App\Modules\Members\Support;

use App\Modules\Members\Enums\Gender;
use App\Modules\Members\Enums\MembershipStatus;
use App\Modules\Members\Models\Member;

/**
 * Profile props shared by the member's own page and the admin view.
 */
class ProfilePresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function present(Member $member): array
    {
        $profile = $member->profileOrNew();

        return [
            'id' => $member->id,
            'name' => $member->name,
            'email' => $member->email,
            'phone' => $member->phone,
            'church' => $member->church?->name,
            'status' => $member->status->value,
            'statusLabel' => $member->status->label(),
            'nickname' => $profile->nickname,
            'photoUrl' => $profile->photo
                ? route('members.photo', ['member' => $member->id, 'v' => $profile->updated_at?->timestamp])
                : null,
            'birthDate' => $profile->birth_date?->toDateString(),
            'gender' => $profile->gender?->value,
            'address' => $profile->address,
            'membershipStatus' => $profile->membership_status->value,
            'membershipLabel' => $profile->membership_status->label(),
            'joinedAt' => $profile->joined_at?->toDateString(),
            'baptized' => $profile->baptized,
            'privacy' => $profile->privacy(),
            'deletionRequestedAt' => $profile->deletion_requested_at?->toIso8601String(),
        ];
    }

    /**
     * Select options for the profile forms.
     *
     * @return array<string, list<array{value: string, label: string}>>
     */
    public static function options(): array
    {
        $options = fn (array $cases) => array_map(fn ($case) => ['value' => $case->value, 'label' => $case->label()], $cases);

        return [
            'genders' => $options(Gender::cases()),
            'membershipStatuses' => $options(MembershipStatus::cases()),
        ];
    }

    /**
     * @return array<string, int>
     */
    public static function photoRules(): array
    {
        $photo = config('modules.members.photo');

        return [
            'maxMb' => intdiv($photo['max_kb'], 1024),
            'minSize' => $photo['min_size'],
            'maxSize' => $photo['max_size'],
        ];
    }
}
