<?php

namespace App\Modules\Prayer\Models;

use App\Models\User;
use App\Modules\Core\Models\Concerns\BelongsToChurch;
use App\Modules\Prayer\Enums\PrayerCategory;
use App\Modules\Prayer\Enums\PrayerStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A prayer request (PRD 4.6). Personal data and the request are encrypted;
 * only Tim Doa, Admin and Superadmin of the branch may read them.
 *
 * @property int $id
 * @property int $church_id
 * @property int|null $user_id
 * @property string $full_name
 * @property int|null $age
 * @property string|null $phone
 * @property string|null $address
 * @property string $body
 * @property PrayerCategory $category
 * @property Carbon $week_start
 * @property PrayerStatus $status
 * @property int|null $prayed_by
 * @property Carbon|null $prayed_at
 * @property Carbon|null $answered_at
 * @property Carbon $created_at
 */
class PrayerRequest extends Model
{
    use BelongsToChurch;

    protected $fillable = [
        'full_name',
        'age',
        'phone',
        'address',
        'body',
        'category',
    ];

    protected $attributes = [
        'status' => 'new',
    ];

    protected $hidden = [
        'full_name',
        'phone',
        'address',
        'body',
    ];

    protected static function booted(): void
    {
        static::creating(function (PrayerRequest $request) {
            $request->week_start ??= static::currentWeekStart();
        });
    }

    protected function casts(): array
    {
        return [
            'full_name' => 'encrypted',
            'phone' => 'encrypted',
            'address' => 'encrypted',
            'body' => 'encrypted',
            'category' => PrayerCategory::class,
            'status' => PrayerStatus::class,
            'week_start' => 'date',
            'prayed_at' => 'datetime',
            'answered_at' => 'datetime',
        ];
    }

    /**
     * Monday of the given week (WIB). Weeks run Monday to Sunday (PRD 4.6).
     */
    public static function weekStartOf(Carbon|string|null $date = null): Carbon
    {
        $timezone = (string) config('modules.prayer.timezone');
        $date = $date === null ? Carbon::now($timezone) : Carbon::parse($date, $timezone);

        return $date->copy()->setTimezone($timezone)->startOfWeek(Carbon::MONDAY)->startOfDay();
    }

    public static function currentWeekStart(): Carbon
    {
        return static::weekStartOf();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function prayedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prayed_by');
    }
}
