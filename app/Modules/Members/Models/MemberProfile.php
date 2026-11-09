<?php

namespace App\Modules\Members\Models;

use App\Modules\Members\Enums\Gender;
use App\Modules\Members\Enums\MembershipStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $user_id
 * @property string|null $nickname
 * @property string|null $photo
 * @property Carbon|null $birth_date
 * @property Gender|null $gender
 * @property string|null $address
 * @property MembershipStatus $membership_status
 * @property Carbon|null $joined_at
 * @property bool $baptized
 * @property array{show_birthday?: bool, show_phone?: bool} $privacy_settings
 * @property Carbon|null $deletion_requested_at
 */
class MemberProfile extends Model
{
    protected $primaryKey = 'user_id';

    public $incrementing = false;

    /**
     * Privacy defaults: nothing is shared with other members until the
     * member opts in (used by later member directory and birthday features).
     */
    public const PRIVACY_DEFAULTS = [
        'show_birthday' => false,
        'show_phone' => false,
    ];

    protected $fillable = [
        'nickname',
        'birth_date',
        'gender',
        'address',
        'membership_status',
        'joined_at',
        'baptized',
        'privacy_settings',
        'deletion_requested_at',
    ];

    protected $attributes = [
        'membership_status' => 'sympathizer',
        'baptized' => false,
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'gender' => Gender::class,
            'address' => 'encrypted',
            'membership_status' => MembershipStatus::class,
            'joined_at' => 'date',
            'baptized' => 'boolean',
            'privacy_settings' => 'array',
            'deletion_requested_at' => 'datetime',
        ];
    }

    /**
     * @return array{show_birthday: bool, show_phone: bool}
     */
    public function privacy(): array
    {
        return [...self::PRIVACY_DEFAULTS, ...array_map('boolval', $this->privacy_settings ?? [])];
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'user_id');
    }
}
