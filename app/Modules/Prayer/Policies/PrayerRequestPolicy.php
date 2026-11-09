<?php

namespace App\Modules\Prayer\Policies;

use App\Models\User;
use App\Modules\Core\Support\CurrentChurch;
use App\Modules\Prayer\Models\PrayerRequest;

/**
 * PRD 4.6: prayer content and sender data are only for Tim Doa, Admin and
 * Superadmin of the branch. Superadmin passes through Gate::before (Core);
 * the branch check backs up the global scope.
 */
class PrayerRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('prayer.view');
    }

    public function mark(User $user, PrayerRequest $request): bool
    {
        return $user->can('prayer.mark') && $this->sameChurch($request);
    }

    public function delete(User $user, PrayerRequest $request): bool
    {
        return $user->can('prayer.delete') && $this->sameChurch($request);
    }

    public function export(User $user): bool
    {
        return $user->can('prayer.export');
    }

    private function sameChurch(PrayerRequest $request): bool
    {
        $current = app(CurrentChurch::class);

        return ! $current->restricts() || $request->church_id === $current->id();
    }
}
