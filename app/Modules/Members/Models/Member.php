<?php

namespace App\Modules\Members\Models;

use App\Models\User;
use App\Modules\Core\Support\CurrentChurch;
use App\Modules\Members\Enums\MemberStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A user seen from the Jemaat module: registration status and approval.
 * Same "users" table as App\Models\User, so the shared model stays small.
 *
 * @property int|null $church_id
 * @property string|null $phone
 * @property MemberStatus $status
 * @property int|null $approved_by
 * @property Carbon|null $approved_at
 * @property string|null $review_note
 */
class Member extends User
{
    protected $table = 'users';

    /**
     * Spatie finds the guard from the auth provider's model (User); name it
     * here because this class is not that model.
     */
    protected string $guard_name = 'web';

    /**
     * Keep the morph class of User so roles (spatie) attached through either
     * model are the same rows.
     */
    public function getMorphClass(): string
    {
        return User::class;
    }

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'status' => MemberStatus::class,
            'approved_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Members of the current branch; every branch for Superadmin and outside
     * web requests (PRD 4.10). Users are not globally scoped (see
     * BelongsToChurch), so admin queries must call this.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function inCurrentChurch(Builder $query): void
    {
        $current = app(CurrentChurch::class);

        if ($current->restricts()) {
            $query->where('church_id', $current->id());
        }
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function withStatus(Builder $query, MemberStatus $status): void
    {
        $query->where('status', $status->value);
    }
}
