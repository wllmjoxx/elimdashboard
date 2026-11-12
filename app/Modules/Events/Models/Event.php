<?php

namespace App\Modules\Events\Models;

use App\Models\User;
use App\Modules\Core\Models\Concerns\BelongsToChurch;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * A church event (Kegiatan). Scoped to the current branch. An event without
 * an end time runs until the end of its start day.
 *
 * @property int $id
 * @property int $church_id
 * @property string $title
 * @property string $slug
 * @property string|null $summary
 * @property string|null $body
 * @property string|null $location
 * @property CarbonImmutable $starts_at
 * @property CarbonImmutable|null $ends_at
 * @property string|null $image_path
 * @property string|null $registration_url
 * @property bool $is_published
 * @property int|null $created_by
 */
class Event extends Model
{
    use BelongsToChurch;

    public const ONGOING = 'ongoing';

    public const UPCOMING = 'upcoming';

    public const PAST = 'past';

    protected $fillable = [
        'title',
        'summary',
        'body',
        'location',
        'starts_at',
        'ends_at',
        'registration_url',
        'is_published',
    ];

    protected static function booted(): void
    {
        static::creating(function (Event $event) {
            $event->slug ??= static::uniqueSlug($event->title, $event->church_id);
        });
    }

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_published' => 'boolean',
        ];
    }

    /**
     * The church's current wall-clock time, in the same form as the stored
     * starts_at/ends_at (local time without a timezone).
     */
    public static function now(): Carbon
    {
        return Carbon::parse(Carbon::now((string) config('modules.events.timezone'))->toDateTimeString());
    }

    /**
     * SQL expression for when the event ends (end of the start day if
     * there is no end time).
     */
    private static function endExpression(Builder $query): string
    {
        return $query->getConnection()->getDriverName() === 'sqlite'
            ? "coalesce(ends_at, date(starts_at) || ' 23:59:59')"
            : 'coalesce(ends_at, timestamp(date(starts_at), \'23:59:59\'))';
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function ongoing(Builder $query): void
    {
        $now = static::now()->toDateTimeString();

        $query->where('starts_at', '<=', $now)
            ->whereRaw(static::endExpression($query).' >= ?', [$now])
            ->orderBy('starts_at');
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function upcoming(Builder $query): void
    {
        $query->where('starts_at', '>', static::now()->toDateTimeString())->orderBy('starts_at');
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function past(Builder $query): void
    {
        $query->whereRaw(static::endExpression($query).' < ?', [static::now()->toDateTimeString()])
            ->orderByDesc('starts_at');
    }

    public function state(): string
    {
        $now = static::now();
        $end = $this->ends_at ?? $this->starts_at->copy()->endOfDay();

        return match (true) {
            $this->starts_at->gt($now) => self::UPCOMING,
            $end->gte($now) => self::ONGOING,
            default => self::PAST,
        };
    }

    public function imageUrl(): ?string
    {
        return $this->image_path
            ? Storage::disk((string) config('modules.events.image.disk'))->url($this->image_path)
            : null;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * URL slug unique within the branch. Set automatically on create; call it
     * directly where model events are off (DatabaseSeeder).
     */
    public static function uniqueSlug(string $title, ?int $churchId): string
    {
        $base = Str::slug($title) ?: 'kegiatan';
        $slug = $base;
        $suffix = 2;

        while (static::query()->withoutGlobalScopes()->where('church_id', $churchId)->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
