<?php

namespace App\Modules\Announcements\Models;

use App\Modules\Core\Models\Concerns\BelongsToChurch;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A church announcement (Pengumuman). Scoped to the current branch.
 *
 * @property int $id
 * @property int $church_id
 * @property string $title
 * @property string $slug
 * @property string $body
 * @property bool $is_pinned
 * @property bool $is_published
 * @property CarbonImmutable $published_on
 * @property CarbonImmutable|null $expires_on
 * @property int|null $created_by
 */
class Announcement extends Model
{
    use BelongsToChurch;

    protected $fillable = [
        'title',
        'body',
        'is_pinned',
        'is_published',
        'published_on',
        'expires_on',
    ];

    protected static function booted(): void
    {
        static::creating(function (Announcement $announcement) {
            $announcement->slug ??= static::uniqueSlug($announcement->title, $announcement->church_id);
        });
    }

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
            'is_published' => 'boolean',
            'published_on' => 'date',
            'expires_on' => 'date',
        ];
    }

    /**
     * Today in church time.
     */
    public static function today(): Carbon
    {
        return Carbon::today((string) config('modules.announcements.timezone'));
    }

    /**
     * Visible to visitors today: published, started and not expired.
     * Pinned first, then newest.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function live(Builder $query): void
    {
        $today = static::today()->toDateString();

        $query->where('is_published', true)
            ->whereDate('published_on', '<=', $today)
            ->where(fn (Builder $query) => $query->whereNull('expires_on')->orWhereDate('expires_on', '>=', $today))
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_on')
            ->orderByDesc('id');
    }

    /**
     * Status shown in the admin list.
     */
    public function stateLabel(): string
    {
        $today = static::today()->toDateString();

        return match (true) {
            ! $this->is_published => 'Draf',
            $this->published_on->toDateString() > $today => 'Terjadwal',
            $this->expires_on !== null && $this->expires_on->toDateString() < $today => 'Berakhir',
            default => 'Tayang',
        };
    }

    public function excerpt(int $limit = 200): string
    {
        return Str::limit(trim(preg_replace('/\s+/', ' ', $this->body) ?? ''), $limit);
    }

    /**
     * URL slug unique within the branch. Set automatically on create; call it
     * directly where model events are off (DatabaseSeeder).
     */
    public static function uniqueSlug(string $title, ?int $churchId): string
    {
        $base = Str::slug($title) ?: 'pengumuman';
        $slug = $base;
        $suffix = 2;

        while (static::query()->withoutGlobalScopes()->where('church_id', $churchId)->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
