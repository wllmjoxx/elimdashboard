<?php

namespace App\Modules\Devotionals\Models;

use App\Models\User;
use App\Modules\Core\Models\Concerns\BelongsToChurch;
use App\Modules\Devotionals\Enums\DevotionalStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A daily devotional (PRD 4.8). Scoped to the current branch.
 *
 * @property int $id
 * @property int $church_id
 * @property string $title
 * @property string $slug
 * @property string $scripture_ref
 * @property string|null $scripture_text
 * @property string $body
 * @property Carbon $publish_date
 * @property DevotionalStatus $status
 * @property int|null $created_by
 * @property int|null $published_by
 * @property Carbon|null $published_at
 */
class Devotional extends Model
{
    use BelongsToChurch;

    protected $fillable = [
        'title',
        'scripture_ref',
        'scripture_text',
        'body',
        'publish_date',
    ];

    protected $attributes = [
        'status' => 'draft',
    ];

    protected static function booted(): void
    {
        static::creating(function (Devotional $devotional) {
            $devotional->slug ??= static::uniqueSlug($devotional->title, $devotional->church_id);
        });
    }

    protected function casts(): array
    {
        return [
            'publish_date' => 'date',
            'status' => DevotionalStatus::class,
            'published_at' => 'datetime',
        ];
    }

    /**
     * Today's date in the church timezone, for publish dates.
     */
    public static function today(): Carbon
    {
        return Carbon::today((string) config('modules.devotionals.timezone'));
    }

    /**
     * Visible to visitors: published and the publish date has come.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function live(Builder $query): void
    {
        $query->where('status', DevotionalStatus::Published->value)
            ->whereDate('publish_date', '<=', static::today()->toDateString())
            ->orderByDesc('publish_date')
            ->orderByDesc('id');
    }

    public function isScheduled(): bool
    {
        // Compare calendar dates: publish_date has no time or timezone.
        return $this->status === DevotionalStatus::Published
            && $this->publish_date->toDateString() > static::today()->toDateString();
    }

    /**
     * Status as shown in the admin list: Draf, Terjadwal or Terbit.
     */
    public function stateLabel(): string
    {
        return $this->isScheduled() ? 'Terjadwal' : $this->status->label();
    }

    /**
     * First words of the body, for teasers and meta descriptions.
     */
    public function excerpt(int $limit = 220): string
    {
        return Str::limit(trim(preg_replace('/\s+/', ' ', $this->body) ?? ''), $limit);
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
        $base = Str::slug($title) ?: 'renungan';
        $slug = $base;
        $suffix = 2;

        while (static::query()->withoutGlobalScopes()->where('church_id', $churchId)->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
